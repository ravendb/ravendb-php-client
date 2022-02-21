<?php

namespace RavenDB\Documents\Session;

use _PHPStan_76800bfb5\Nette\NotImplementedException;
use InvalidArgumentException;
use PHPUnit\Framework\PHPTAssertionFailedError;
use Ramsey\Uuid\UuidInterface;
use RavenDB\Constants\Metadata;
use RavenDB\Documents\Commands\Batches\BatchOptions;
use RavenDB\Documents\Commands\Batches\CommandDataInterface;
use RavenDB\Documents\Commands\Batches\CommandType;
use RavenDB\Documents\Commands\Batches\DeleteCommandData;
use RavenDB\Documents\Commands\Batches\ForceRevisionCommandData;
use RavenDB\Documents\Commands\Batches\PutCommandDataWithJson;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreBase;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Identity\GenerateEntityIdOnTheClient;
use RavenDB\Documents\IdTypeAndName;
use RavenDB\Exceptions\Documents\Session\NonUniqueObjectException;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\RequestExecutor;
use RavenDB\Json\BatchCommandResult;
use RavenDB\Json\JsonOperation;
use RavenDB\Json\MetadataAsDictionary;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Primitives\ClosureArray;
use RavenDB\Primitives\EventHelper;
use RavenDB\Type\StringArray;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use function PHPUnit\Framework\isEmpty;

use DS\Map as DSMap;

abstract class InMemoryDocumentSessionOperations implements CleanCloseable
{
    protected bool $isDisposed = false;

    protected UuidInterface $id;
    protected string $databaseName;

    protected DocumentStoreBase $documentStore;
    protected RequestExecutor $requestExecutor;
    public bool $noTracking;
    protected TransactionMode $transactionMode;
    public bool $disableAtomicDocumentWritesInClusterWideTransaction;

    protected SessionInfo $sessionInfo;
    public ?BatchOptions $saveChangesOptions = null;

    protected int $numberOfRequests = 0;
    protected int $maxNumberOfRequestsPerSession;
    protected bool $useOptimisticConcurrency = false;

    protected array $knownMissingIds = [];

    protected bool $generateDocumentKeysOnStore = true;

    public DocumentsById $documentsById;
    public DocumentInfoArray $includedDocumentsById;

    private GenerateEntityIdOnTheClient $generateEntityIdOnTheClient;

    private EntityToJson $entityToJson;

    public DocumentsByEntityHolder $documentsByEntity;

    public DeletedEntitiesHolder $deletedEntities;

    abstract protected function generateId(?object $entity): string;

    public array $deferredCommands = [];
    public ?DSMap $deferredCommandsMap = null;

    public array $idsForCreatingForcedRevisions = [];

    private array $countersByDocId = [];
    private array $timeSeriesByDocId = [];

    private ClosureArray $onBeforeStore;
    private ClosureArray $onAfterSaveChanges;
    private ClosureArray $onBeforeDelete;
    private ClosureArray $onBeforeQuery;

    private ClosureArray $onBeforeConversionToDocument;
    private ClosureArray $onAfterConversionToDocument;
    private ClosureArray $onBeforeConversionToEntity;
    private ClosureArray $onAfterConversionToEntity;

    /**
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     */
    public function __construct(DocumentStoreBase $documentStore, UuidInterface $id, SessionOptions $options)
    {
        $this->onBeforeStore = new ClosureArray();
        $this->onAfterSaveChanges = new ClosureArray();
        $this->onBeforeDelete = new ClosureArray();
        $this->onBeforeQuery = new ClosureArray();

        $this->onBeforeConversionToDocument = new ClosureArray();
        $this->onAfterConversionToDocument = new ClosureArray();
        $this->onBeforeConversionToEntity = new ClosureArray();
        $this->onAfterConversionToEntity = new ClosureArray();

        $this->documentsByEntity = new DocumentsByEntityHolder();
        $this->deletedEntities = new DeletedEntitiesHolder();

        $this->documentsById = new DocumentsById();
        $this->includedDocumentsById = new DocumentInfoArray();

        $this->entityToJson = new EntityToJson($this);

        $this->id = $id;

        $this->databaseName = $options->getDatabase() ?? $documentStore->getDatabase();

        if (empty($this->databaseName)) {
            throw new IllegalStateException("Cannot open a Session without specifying a name of a database " .
                "to operate on. Database name can be passed as an argument when Session is" .
                " being opened or default database can be defined using 'DocumentStore.setDatabase()' method");
        }

        $this->documentStore = $documentStore;
        $this->requestExecutor =
            $options->getRequestExecutor() ?? $documentStore->getRequestExecutor($this->databaseName);

        $this->noTracking = $options->isNoTracking();


        $this->useOptimisticConcurrency = $this->requestExecutor->getConventions()->isUseOptimisticConcurrency();
        $this->maxNumberOfRequestsPerSession =
            $this->requestExecutor->getConventions()->getMaxNumberOfRequestsPerSession();

        $genFunction = function (?object $entity) {
            return $this->generateId($entity);
        };
        $this->generateEntityIdOnTheClient =
            new GenerateEntityIdOnTheClient($this->requestExecutor->getConventions(), $genFunction);
        $this->entityToJson = new EntityToJson($this);

        $this->sessionInfo = new SessionInfo($this, $options, $this->documentStore);
        $this->transactionMode = $options->getTransactionMode();

        $this->disableAtomicDocumentWritesInClusterWideTransaction =
            $options->getDisableAtomicDocumentWritesInClusterWideTransaction();

        $this->deferredCommands = [];
        $this->deferredCommandsMap = new DSMap();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getDocumentStore(): DocumentStoreInterface
    {
        return $this->documentStore;
    }

    public function getRequestExecutor(): RequestExecutor
    {
        return $this->requestExecutor;
    }

    public function getSessionInfo(): SessionInfo
    {
        return $this->sessionInfo;
    }

    public function getTransactionMode(): TransactionMode
    {
        return $this->transactionMode;
    }

    public function setTransactionMode(TransactionMode $transactionMode): void
    {
        $this->transactionMode = $transactionMode;
    }

    public function getDocumentConventions(): DocumentConventions
    {
        return $this->requestExecutor->getConventions();
    }

    public function getNumberOfRequests(): int
    {
        return $this->numberOfRequests;
    }

    public function getMaxNumberOfRequestsPerSession(): int
    {
        return $this->maxNumberOfRequestsPerSession;
    }

    public function setMaxNumberOfRequestsPerSession(int $maxNumberOfRequestsPerSession): void
    {
        $this->maxNumberOfRequestsPerSession = $maxNumberOfRequestsPerSession;
    }

    public function isUseOptimisticConcurrency(): bool
    {
        return $this->useOptimisticConcurrency;
    }

    public function setUseOptimisticConcurrency(bool $useOptimisticConcurrency): void
    {
        $this->useOptimisticConcurrency = $useOptimisticConcurrency;
    }

    public function getConventions(): DocumentConventions
    {
        return $this->requestExecutor->getConventions();
    }

    public function getGenerateEntityIdOnTheClient(): GenerateEntityIdOnTheClient
    {
        return $this->generateEntityIdOnTheClient;
    }

    public function getEntityToJson(): EntityToJson
    {
        return $this->entityToJson;
    }

    public function close(): void
    {
        if ($this->isDisposed) {
            return;
        }

        // todo: implement this
//        EventHelper.invoke(onSessionClosing, this, new SessionClosingEventArgs(this));

        $this->isDisposed = true;

        // nothing more to do for now
    }

    public function checkIfIdAlreadyIncluded(StringArray $ids, StringArray $includes): bool
    {
        foreach ($ids as $id) {
            if (in_array($id, $this->knownMissingIds)) {
                continue;
            }

            $documentInfo = $this->documentsById->getValue($id);
            if ($documentInfo == null) {
                $documentInfo = $this->includedDocumentsById->getValue($id);
                if ($documentInfo == null) {
                    return false;
                }
            }

            if ($documentInfo->getEntity() == null && $documentInfo->getDocument() == null) {
                return false;
            }

//          @todo: check this code - it doesnt do anything so it's commented out
//
//            if (includes == null) {
//                continue;
//            }
//
//            for (String include : includes) {
//                final boolean[] hasAll = {true}; //using fake array here to force final keyword on variable
//
//                IncludesUtil.include(documentInfo.getDocument(), include, s -> hasAll[0] &= isLoaded(s));
//
//                if (!hasAll[0]) {
//                    return false;
//                }
//
//            }
//
        }

        return true;
    }

    public function incrementRequestCount(): void
    {
        $this->numberOfRequests += 1;
        if ($this->numberOfRequests > $this->maxNumberOfRequestsPerSession) {
            throw new IllegalStateException(sprintf(
                "The maximum number of requests (%d) allowed for this session has been reached. Raven limits " .
                "the number of remote calls that a session is allowed to make as an early warning system.".
                "Sessions are expected to be short lived, and Raven provides facilities like load(String[] keys) to " .
                "load multiple documents at once and batch saves (call SaveChanges() only once)." .
                "You can increase the limit by setting DocumentConvention.MaxNumberOfRequestsPerSession " .
                "or MaxNumberOfRequestsPerSession, but it is advisable that you'll look into reducing the " .
                "number of remote calls first, since that will speed up your application significantly " .
                "and result in a more responsive application.",
                $this->maxNumberOfRequestsPerSession
            ));
        }
    }

    public function registerMissing(StringArray $ids): void
    {
        if ($this->noTracking) {
            return;
        }

        $this->knownMissingIds = array_merge($this->knownMissingIds, $ids->getArrayCopy());
    }

    public function registerIncludes(array $includes): void
    {
        if ($this->noTracking) {
            return;
        }

        if (empty($this->includes)) {
            return;
        }

//        for (String fieldName : Lists.newArrayList(includes.fieldNames())) {
//            JsonNode fieldValue = includes.get(fieldName);
//
//            if (fieldValue == null) {
//                continue;
//            }
//
//            ObjectNode json = (ObjectNode) fieldValue;
//
//            DocumentInfo newDocumentInfo = DocumentInfo.getNewDocumentInfo(json);
//            if (JsonExtensions.tryGetConflict(newDocumentInfo.getMetadata())) {
//                continue;
//            }
//
//            includedDocumentsById.put(newDocumentInfo.getId(), newDocumentInfo);
//        }
    }

    public function registerMissingIncludes(array $results, array $includes, StringArray $includePaths): void
    {

    }
//    public void registerMissingIncludes(ArrayNode results, ObjectNode includes, String[] includePaths) {
//
//        if (noTracking) {
//            return;
//        }
//
//        if (includePaths == null || includePaths.length == 0) {
//            return;
//        }
//
//        for (JsonNode result : results) {
//            for (String include : includePaths) {
//                if (Constants.Documents.Indexing.Fields.DOCUMENT_ID_FIELD_NAME.equals(include)) {
//                    continue;
//                }
//
//                IncludesUtil.include((ObjectNode) result, include, id -> {
//                    if (id == null) {
//                        return;
//                    }
//
//                    if (isLoaded(id)) {
//                        return;
//                    }
//
//                    JsonNode document = includes.get(id);
//                    if (document != null) {
//                        JsonNode metadata = document.get(Constants.Documents.Metadata.KEY);
//
//                        if (JsonExtensions.tryGetConflict(metadata)) {
//                            return;
//                        }
//                    }
//
//                    registerMissing(id);
//                });
//            }
//        }
//    }

    public function registerCounters(
        array $resultCounters,
        StringArray $ids,
        StringArray $countersToInclude,
        bool $gotAll
    ): void {
        // @todo: implement this
    }


    public function registerTimeSeries(array $resultTimeSeries): void
    {
        // @todo: implement this
    }

    public function isDeleted(string $id): bool
    {
        return in_array($id, $this->knownMissingIds);
    }

    /**
     * @throws IllegalStateException
     * @throws ExceptionInterface
     */
    public function trackEntity(string $className, DocumentInfo $doc): ?object
    {
        return $this->trackEntityInternal(
            $className,
            $doc->getId(),
            $doc->getDocument(),
            $doc->getMetadata(),
            $this->noTracking
        );
    }

    /**
     * @throws ExceptionInterface
     * @throws IllegalStateException
     */
    public function trackEntityInternal(
        string $entityType,
        string $id,
        array $document,
        array $metadata,
        bool $noTracking
    ): ?object {
        // if noTracking is session-wide then we want to override the passed argument
        $noTracking = $this->noTracking || $noTracking;

        if (empty($id)) {
            return $this->deserializeFromTransformer($entityType, null, $document, false);
        }

        $docInfo = $this->documentsById->getValue($id);
        if ($docInfo != null) {
            // the local instance may have been changed, we adhere to the current Unit of Work
            // instance, and return that, ignoring anything new.

            if ($docInfo->getEntity() == null) {
                $docInfo->setEntity($this->entityToJson->convertToEntity($entityType, $id, $document, !$noTracking));
            }

            if (!$noTracking) {
                if ($this->includedDocumentsById->offsetExists($id)) {
                    $this->includedDocumentsById->offsetUnset($id);
                }
                $this->documentsByEntity->put($docInfo->getEntity(), $docInfo);
            }

            return $docInfo->getEntity();
        }

        $docInfo = $this->includedDocumentsById[$id];
        if ($docInfo != null) {
            if ($docInfo->getEntity() == null) {
                $docInfo->setEntity($this->entityToJson->convertToEntity($entityType, $id, $document, !$noTracking));
            }

            if (!$noTracking) {
                unset($this->includedDocumentsById[$id]);
                $this->documentsById->add($docInfo);
                $this->documentsByEntity->put($docInfo->getEntity(), $docInfo);
            }

            return $docInfo->getEntity();
        }

        $entity = $this->entityToJson->convertToEntity($entityType, $id, $document, !$noTracking);

        $changeVector = $metadata['@change-vector'];
        if (empty($changeVector)) {
            throw new IllegalStateException("Document " . $id . " must have Change Vector");
        }

        if (!$noTracking) {
            $newDocumentInfo = new DocumentInfo();
            $newDocumentInfo->setId($id);
            $newDocumentInfo->setDocument($document);
            $newDocumentInfo->setMetadata($metadata);
            $newDocumentInfo->setEntity($entity);
            $newDocumentInfo->setChangeVector($changeVector);

            $this->documentsById->add($newDocumentInfo);
            $$this->documentsByEntity->put($entity, $newDocumentInfo);
        }

        return $entity;
    }

    /**
     * @throws ExceptionInterface
     */
    private function deserializeFromTransformer(string $entityType, string $id, array $document, bool $trackEntity)
    {
        return $this->entityToJson->convertToEntity($entityType, $id, $document, $trackEntity);
    }

    /**
     *
     * Marks the specified entity for deletion. The entity will be deleted when IDocumentSession.saveChanges is called.
     *
     * WARNING: This method when used with string entityId will not call beforeDelete listener!
     *
     * @param string|object|null $entity
     * @param string|null $changeVector
     *
     * @throws IllegalArgumentException
     * @throws IllegalStateException
     */
    public function delete($entity, ?string $changeVector = null): void
    {
        if (is_object($entity)) {
            $this->deleteEntity($entity);
            return;
        }

        $this->deleteById($entity, $changeVector);
    }

    /**
     * Marks the specified entity for deletion. The entity will be deleted when SaveChanges is called.
     *
     * @param ?object $entity Entity to delete
     *
     * @throws IllegalArgumentException
     * @throws IllegalStateException
     */
    protected function deleteEntity(?object $entity): void
    {
        if ($entity === null) {
            throw new IllegalArgumentException("Entity cannot be null");
        }

        $value = $this->documentsByEntity->get($entity);

        if ($value == null) {
            throw new IllegalStateException($entity . ' is not associated with the session, cannot delete unknown entity instance');
        }

        $this->deletedEntities->add($entity);
        $this->includedDocumentsById->remove($value->getId());
        if ($this->countersByDocId !== null) {
            if (($key = array_search($value->getId(), $this->countersByDocId)) !== false) {
                unset($this->countersByDocId[$key]);
            }
        }
        $this->knownMissingIds[] = $value->getId();
    }

    /**
     * Marks the specified entity for deletion. The entity will be deleted when IDocumentSession.SaveChanges is called.
     * WARNING: This method will not call beforeDelete listener!
     *
     * @param ?string $id
     * @param ?string $expectedChangeVector
     *
     * @throws IllegalArgumentException
     * @throws IllegalStateException
     */
    protected function deleteById(?string $id, ?string $expectedChangeVector = null): void
    {
        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        $changeVector = null;
        /** @var DocumentInfo $documentInfo */
        $documentInfo = $this->documentsById->getValue($id);
        if ($documentInfo != null) {
            $newObj = $this->entityToJson->convertEntityToJson($documentInfo->getEntity(), $documentInfo);
            if ($documentInfo->getEntity() != null && $this->isEntityChanged($newObj, $documentInfo)) {
                throw new IllegalStateException("Can't delete changed entity using identifier. Use delete(?object entity) instead.");
            }

            if ($documentInfo->getEntity() != null) {
                $this->documentsByEntity->remove($documentInfo->getEntity());
            }

            $this->documentsById->remove($id);
            $changeVector = $documentInfo->getChangeVector();
        }

        $this->knownMissingIds[] = $id;
        $changeVector = $this->isUseOptimisticConcurrency() ? $changeVector : null;
        if ($this->countersByDocId !== null) {
            if (($key = array_search($value->getId(), $this->countersByDocId)) !== false) {
                unset($this->countersByDocId[$key]);
            }
        }


//        defer(new DeleteCommandData(
//                id,
//                ObjectUtils.firstNonNull(expectedChangeVector, changeVector),
//                ObjectUtils.firstNonNull(expectedChangeVector, documentInfo != null ? documentInfo.getChangeVector() : null
//            )));
    }


    /**
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     * @throws NonUniqueObjectException
     */
    public function store(?object $entity, ?string $id = null, ?string $changeVector = null): void
    {
        $concurrencyCheckMode = ConcurrencyCheckMode::auto();

        if ($id == null) {
            if (!$this->generateEntityIdOnTheClient->entityHasId($entity)) {
                $concurrencyCheckMode = ConcurrencyCheckMode::forced();
            }
        } else {
            if ($changeVector == null) {
                $concurrencyCheckMode = ConcurrencyCheckMode::disabled();
            } else {
                $concurrencyCheckMode = ConcurrencyCheckMode::forced();
            }
        }

        $this->storeInternal($entity, $id, $changeVector, $concurrencyCheckMode);
    }

    /**
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     * @throws NonUniqueObjectException
     * @throws ExceptionInterface
     */
    private function storeInternal(
        ?object $entity,
        ?string $id,
        ?string $changeVector,
        ConcurrencyCheckMode $forceConcurrencyCheck
    ): void {
        if ($this->noTracking) {
            throw new IllegalStateException(
                "Cannot store entity. Entity tracking is disabled in this session."
            );
        }
        if ($entity == null) {
            throw new InvalidArgumentException("Entity cannot be null");
        }

        $value = $this->documentsByEntity->get($entity);

        if ($value != null) {
            $value->setChangeVector($changeVector ?? $value->getChangeVector());
            $value->setConcurrencyCheckMode($forceConcurrencyCheck);

            return;
        }

        if ($id == null) {
            if ($this->generateDocumentKeysOnStore) {
                $id = $this->generateEntityIdOnTheClient->generateDocumentKeyForStorage($entity);
            } else {
                $this->rememberEntityForDocumentIdGeneration($entity);
            }
        } else {
            // Store it back into the Id field so the client has access to it
            $this->generateEntityIdOnTheClient->trySetIdentity($entity, $id);
        }

        $key = IdTypeAndName::create($id, CommandType::clientAnyCommand(), null);
        if ($this->deferredCommandsMap->hasKey($key)) {
            throw new IllegalStateException(
              "Can't store document, there is a deferred command registered for this document in the session."
              . " Document id: " . $id
            );
        }

        if ($this->deletedEntities->contains($entity)) {
            throw new IllegalStateException(
              "Can't store object, it was already deleted in this session. Document id: " . $id
            );
        }


        // we make the check here even if we just generated the ID
        // users can override the ID generation behavior, and we need
        // to detect if they generate duplicates.
        $this->assertNoNonUniqueInstance($entity, $id);

        $collectionName = $this->requestExecutor->getConventions()->getCollectionName($entity);

        $mapper = JsonExtensions::getDefaultMapper();
        $metadata = [];

        if ($collectionName != null) {
            $metadata[Metadata::COLLECTION] = $mapper->normalize($collectionName, 'json');
        }

        // @todo: Check why do we need this for
//        String javaType = _requestExecutor.getConventions().getJavaClassName(entity.getClass());
//        if (javaType != null) {
//            metadata.set(Constants.Documents.Metadata.RAVEN_JAVA_TYPE, mapper.convertValue(javaType, TextNode.class));
//        }

        if ($id != null) {
            $this->removeIdFromKnownMissingIds($id);
        }

        $this->storeEntityInUnitOfWork($id, $entity, $changeVector, $metadata, $forceConcurrencyCheck);
    }

    private function rememberEntityForDocumentIdGeneration(object $entity)
    {
        throw new NotImplementedException(
            "You cannot set GenerateDocumentIdsOnStore to false " .
            "without implementing RememberEntityForDocumentIdGeneration"
        );
    }

    private function removeIdFromKnownMissingIds(string $id): void
    {
        if (($key = array_search($id, $this->knownMissingIds)) !== false) {
            unset($this->knownMissingIds[$key]);
        }
    }

    protected function storeEntityInUnitOfWork(
        ?string $id,
        ?object $entity,
        ?string $changeVector,
        array $metadata,
        ConcurrencyCheckMode $forceConcurrencyCheck
    ): void {
        if ($id != null) {
            $this->removeIdFromKnownMissingIds($id);
        }

        $documentInfo = new DocumentInfo();
        $documentInfo->setId($id);
        $documentInfo->setMetadata($metadata);
        $documentInfo->setChangeVector($changeVector);
        $documentInfo->setConcurrencyCheckMode($forceConcurrencyCheck);
        $documentInfo->setEntity($entity);
        $documentInfo->setNewDocument(true);
        $documentInfo->setDocument([]);

        $this->documentsByEntity->put($entity, $documentInfo);

        if ($id != null) {
            $this->documentsById->add($documentInfo);
        }
    }

    /**
     * @throws NonUniqueObjectException
     */
    protected function assertNoNonUniqueInstance(object $entity, string $id): void
    {
        if (isEmpty($id)
            || $id[strlen($id) - 1] == '|'
            || $id[strlen($id) - 1] == $this->getConventions()->getIdentityPartsSeparator()) {
            return;
        }
        $info = $this->documentsById->getValue($id);
        if ($info == null || $info->getEntity() == $entity) {
            return;
        }

        throw new NonUniqueObjectException("Attempted to associate a different object with id '" . $id . "'.");
    }

    /**
     * @throws ExceptionInterface
     * @throws IllegalArgumentException
     */
    public function whatChanged(): array
    {
        $changes = $this->getAllEntitiesChanges();

        $this->prepareForEntitiesDeletion(null, $changes);

        return $changes;
    }

    /**
     * @throws ExceptionInterface
     * @throws IllegalArgumentException
     */
    private function getAllEntitiesChanges(): array
    {
        /** @var array<string, DocumentsChangesArray> $changes */
        $changes = array();

        foreach ($this->documentsById as $id => $documentInfo) {
            $this->updateMetadataModifications($documentInfo);
            $newObj = $this->entityToJson->convertEntityToJson($documentInfo->getEntity(), $documentInfo);
            $entityChanges = $this->getEntityChanges($newObj, $documentInfo);
            if (count($entityChanges)) {
                $changes[$documentInfo->getId()] = $entityChanges;
            }
        }

        return $changes;
    }

    /**
     * Evicts the specified entity from the session.
     * Remove the entity from the delete queue and stops tracking changes for this entity.
     *
     * @throws IllegalStateException
     */
    public function evict(object $entity): void
    {
        $documentInfo = $this->documentsByEntity->get($entity);
        if ($documentInfo != null) {
            $this->documentsByEntity->evict($entity);
            $this->documentsById->remove($documentInfo->getId());

            if (array_key_exists($documentInfo->getId(), $this->countersByDocId)) {
                unset($this->countersByDocId[$documentInfo->getId()]);
            }
            if (array_key_exists($documentInfo->getId(), $this->timeSeriesByDocId)) {
                unset($this->timeSeriesByDocId[$documentInfo->getId()]);
            }
        }
        $this->deletedEntities->evict($entity);
        $this->entityToJson->removeFromMissing($entity);

    }

        /**
         * Clears this instance.
         * Remove all entities from the delete queue and stops tracking changes for all entities.
         */
    public function clear(): void
    {
        $this->documentsByEntity->clear();
        $this->deletedEntities->clear();
        $this->documentsById->clear();
        unset($this->knownMissingIds);
        $this->knownMissingIds = [];
        if ($this->countersByDocId != null) {
            $this->countersByDocId = [];
        }
        $this->deferredCommands = [];
        $this->deferredCommandsMap->clear();
//        $this->clearClusterSession();
//        $this->pendingLazyOperations->clear();
//        $this->entityToJson->clear();
    }

    /**
     * Defer commands to be executed on saveChanges()
     *
     * @param CommandDataInterface $command  Command to defer
     * @param ?array $commands More commands to defer
     */
    public function defer(CommandDataInterface $command, array $commands = []): void
    {
        $this->deferredCommands[] = $command;
        $this->deferInternal($command);

        if (count($commands) > 0) {
            $this->deferCommands($commands);
        }
    }

    /**
     * Defer commands to be executed on saveChanges()
     *
     * @param array $commands Commands to defer
     */
    public function deferCommands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->deferredCommands[] = $command;
        }

        foreach ($commands as $command) {
            $this->deferInternal($command);
        }
    }

    // @todo: implement this method fully
    private function deferInternal(CommandDataInterface $command): void
    {
        if ($command->getType()->isBatchPatch()) {
            /** @var BatchPatchCommandData $batchPatchCommand */
            $batchPatchCommand = $command;
//            for (BatchPatchCommandData.IdAndChangeVector kvp : batchPatchCommand.getIds()) {
//                addCommand(command, kvp.getId(), CommandType.PATCH, command.getName());
//            }
//            return;
        }

        $this->addCommand($command, $command->getId(), $command->getType(), $command->getName());
    }

    private function addCommand(CommandDataInterface $command, string $id, CommandType $commandType, string $commandName): void
    {
        $this->deferredCommandsMap->put(IdTypeAndName::create($id, $commandType, $commandName), $command);
        $this->deferredCommandsMap->put(IdTypeAndName::create($id, CommandType::clientAnyCommand(), null), $command);

        if (!$command->getType()->isAttachmentPut() &&
            !$command->getType()->isAttachmentDelete() &&
            !$command->getType()->isAttachmentCopy() &&
            !$command->getType()->isAttachmentMove() &&
            !$command->getType()->isCounters() &&
            !$command->getType()->isTimeSeries() &&
            !$command->getType()->isTimeSeriesCopy()
        ) {
            $this->deferredCommandsMap->put(IdTypeAndName::create($id, CommandType::clientModifyDocumentCommand(), null), $command);
        }
    }

    public function prepareForSaveChanges(): SaveChangesData
    {
        $result = new SaveChangesData($this);
        $deferredCommandsCount = count($this->deferredCommands);

        // @todo: CONTINUE HERE !!!! implement following lines
        $this->prepareForEntitiesDeletion($result);
        $this->prepareForEntitiesPuts($result);
//        $this->prepareForCreatingRevisionsFromIds($result);
//        $this->prepareCompareExchangeEntities($result);

        if (count($this->deferredCommands) > $deferredCommandsCount) {
            // this allow OnBeforeStore to call Defer during the call to include
            // additional values during the same SaveChanges call

            for ($i=$deferredCommandsCount; $i < count($this->deferredCommands); $i++) {
                $result->getDeferredCommands()[] = $this->deferredCommands[$i];
            }

            foreach ($this->deferredCommandsMap as $key => $value) {
                $result->getDeferredCommandsMap()->put($key, $value);
            }
        }

        /** @var CommandDataInterface $deferredCommand */
        foreach ($result->getDeferredCommands() as $deferredCommand) {
            $deferredCommand->onBeforeSaveChanges($this);
        }

        return $result;
    }

    /**
     * @throws NonUniqueObjectException
     * @throws IllegalArgumentException
     * @throws IllegalStateException
     */
    private function getDocumentInfo(object $instance): DocumentInfo
    {
        $documentInfo = $this->documentsByEntity->get($instance);

        if ($documentInfo != null) {
            return $documentInfo;
        }

        $id = $this->generateEntityIdOnTheClient->tryGetIdFromInstance($instance);
        if ($id == null) {
            throw new IllegalStateException("Could not find the document id for " . $instance);
        }

        $this->assertNoNonUniqueInstance($instance, $id);

        throw new IllegalArgumentException("Document " . $id . " doesn't exist in the session");
    }

    public function getMetadataFor(?object $instance): MetadataDictionaryInterface
    {
        if ($instance == null) {
            throw new IllegalArgumentException('Instance cannot be null');
        }

        $documentInfo = $this->getDocumentInfo($instance);
        if ($documentInfo->getMetadataInstance() != null) {
            return $documentInfo->getMetadataInstance();
        }

        $metadataAsJson = $documentInfo->getMetadata();
        $metadata = new MetadataAsDictionary($metadataAsJson);

        $documentInfo->setMetadataInstance($metadata);
        return $metadata;
    }

    /**
     * @throws IllegalStateException
     * @throws IllegalArgumentException
     */
    private function prepareForEntitiesDeletion(?SaveChangesData $result, ?array &$changes = null): void
    {
        $deletes = $this->deletedEntities->prepareEntitiesDeletes();
        try {
            /** @var DeletedEntitiesEnumeratorResult $deletedEntity */
            foreach ($this->deletedEntities->getDeletedEntitiesEnumeratorResults() as $deletedEntity) {
                $documentInfo = $this->documentsByEntity->get($deletedEntity->getEntity());
                if ($documentInfo == null) {
                    continue;
                }

                if ($changes != null) {
                    $docChanges = [];

                    $change = new DocumentsChanges();
                    $change->setFieldNewValue([]);
                    $change->setFieldOldValue([]);
                    $change->setChange(ChangeType::documentDeleted());

                    $docChanges[] = $change;
                    $changes[$documentInfo->getId()] = $docChanges;
                } else {
                    $command = null;
                    $idTypeAndName = IdTypeAndName::create($documentInfo->getId(), CommandType::clientAnyCommand(), null);
                    if ($result->getDeferredCommandsMap()->hasKey($idTypeAndName)) {
                        $command = $result->getDeferredCommandsMap()->get($idTypeAndName);
                    }

                    if ($command != null) {
                        $this->throwInvalidDeletedDocumentWithDeferredCommand($command);
                    }

                    $changeVector = null;
                    $documentInfo = $this->documentsById->getValue($documentInfo->getId());

                    if ($documentInfo != null) {
                        $changeVector = $documentInfo->getChangeVector();

                        if ($documentInfo->getEntity() != null) {
                            $result->getOnSuccess()->removeDocumentByEntity($documentInfo->getEntity());
                            $result->addEntity($documentInfo->getEntity());
                        }

                        $result->getOnSuccess()->removeDocumentById($documentInfo->getId());
                    }

                    if (!$this->useOptimisticConcurrency) {
                        $changeVector = null;
                    }

                    $this->onBeforeDeleteInvoke(new BeforeDeleteEventArgs($this, $documentInfo->getId(), $documentInfo->getEntity()));
                    $deleteCommandData = new DeleteCommandData($documentInfo->getId(), $changeVector, $documentInfo->getChangeVector());
                    $result->addSessionCommand($deleteCommandData);
                }

                if ($changes == null) {
                    $result->getOnSuccess()->clearDeletedEntities();
                }
            }

        } finally {
            $deletes->close();
        }
    }

    // @todo: implement this method
    private function prepareForEntitiesPuts(?SaveChangesData $result): void
    {
        $putsContext = $this->documentsByEntity->prepareEntitiesPuts();

        try {
            $shouldIgnoreEntityChanges = $this->getConventions()->getShouldIgnoreEntityChanges();

            /** @var DocumentsByEntityEnumeratorResult $entity */
            foreach($this->documentsByEntity->getDocumentsByEntityEnumeratorResults() as $entity) {

                if ($entity->getValue()->isIgnoreChanges()) {
                    continue;
                }

                if ($shouldIgnoreEntityChanges != null) {
                    if ($shouldIgnoreEntityChanges->check(
                            $this,
                            $entity->getValue()->getEntity(),
                            $entity->getValue()->getId())) {
                        continue;
                    }
                }

                if ($this->isDeleted($entity->getValue()->getId())) {
                    continue;
                }

                $dirtyMetadata = $this->updateMetadataModifications($entity->getValue());

                $document = $this->entityToJson->convertEntityToJson($entity->getKey(), $entity->getValue());

                if (!$this->isEntityChanged($document, $entity->getValue()) && !$dirtyMetadata) {
                    continue;
                }

                $command = null;
                $idTypeAndName = IdTypeAndName::create($entity->getValue()->getId(), CommandType::clientModifyDocumentCommand(), null);
                if ($result->getDeferredCommandsMap()->hasKey($idTypeAndName)) {
                    $command = $result->getDeferredCommandsMap()->get($idTypeAndName);
                }
                if ($command != null) {
                    $this->throwInvalidModifiedDocumentWithDefferedCommand($command);
                }

                $onBeforeStore = $this->onBeforeStore;

                if (count($onBeforeStore) && $entity->isExecuteOnBeforeStore()) {
                    $beforeStoreEventArgs = new BeforeStoreEventArgs($this, $entity->getValue()->getId(), $entity->getKey());

                    EventHelper::invoke($onBeforeStore, $this, $beforeStoreEventArgs);


                    if ($beforeStoreEventArgs->isMetadataAccessed()) {
                        $this->updateMetadataModifications($entity->getValue());
                    }

                    if ($beforeStoreEventArgs->isMetadataAccessed() || $this->isEntityChanged($document, $entity->getValue())) {
                        $document = $this->entityToJson->convertEntityToJson($entity->getKey(), $entity->getValue());
                    }
                }

                $result->addEntity($entity->getKey());

                if ($entity->getValue()->getId() != null) {
                    $result->getOnSuccess()->removeDocumentById($entity->getValue()->getId());
                }

                $result->getOnSuccess()->updateEntityDocumentInfo($entity->getValue(), $document);

                $changeVector = null;
                if ($this->useOptimisticConcurrency) {
                    if ($entity->getValue()->getConcurrencyCheckMode()->isDisabled()) {
                        // if the user didn't provide a change vector, we'll test for an empty one
                        $changeVector = $entity->getValue()->getChangeVector() ?? "";
                    } else {
                        $changeVector = null;
                    }
                } else if ($entity->getValue()->getConcurrencyCheckMode()->isForced()) {
                    $changeVector = $entity->getValue()->getChangeVector();
                } else {
                    $changeVector = null;
                }

                $forceRevisionCreationStrategy = ForceRevisionStrategy::none();

                if ($entity->getValue()->getId() != null) {
                    if (array_key_exists($entity->getValue()->getId(), $this->idsForCreatingForcedRevisions)) {
                        // Check if user wants to Force a Revision
                        $creationStrategy = $this->idsForCreatingForcedRevisions[$entity->getValue()->getId()];
                        if ($creationStrategy != null) {
                            unset($this->idsForCreatingForcedRevisions[$entity->getValue()->getId()]);
                            $forceRevisionCreationStrategy = $creationStrategy;
                        }
                    }
                }

                $result->addSessionCommand(
                    new PutCommandDataWithJson(
                        $entity->getValue()->getId(),
                        $changeVector,
                        $entity->getValue()->getChangeVector(),
                        $document,
                        $forceRevisionCreationStrategy
                    )
                );
            }

        } finally {
            $putsContext->close();
        }
    }

    private function prepareForCreatingRevisionsFromIds(SaveChangesData $result): void
    {
        // Note: here there is no point checking 'Before' or 'After' because if there were changes then forced revision is done from the PUT command....

        foreach (array_keys($this->idsForCreatingForcedRevisions) as $idEntry) {
            $result->addSessionCommand(new ForceRevisionCommandData($idEntry));
        }

        $this->idsForCreatingForcedRevisions = [];
    }

    private function prepareCompareExchangeEntities(SaveChangesData $result): void
    {
        if (!$this->hasClusterSession()) {
            return;
        }

        $clusterTransactionOperations = $this->getClusterSession();
        if ($clusterTransactionOperations->getNumberOfTrackedCompareExchangeValues() == 0) {
            return;
        }

        if (!$this->transactionMode->isClusterWide()) {
            throw new IllegalStateException("Performing cluster transaction operation require the TransactionMode to be set to CLUSTER_WIDE");
        }

        $this->getClusterSession()->prepareCompareExchangeEntities($result);
    }

    /**
     * @throws ExceptionInterface
     */
    private function updateMetadataModifications(DocumentInfo $documentInfo): bool
    {
        $dirty = false;
        $mapper = JsonExtensions::getDefaultMapper();
        if ($documentInfo->getMetadataInstance() != null) {
            if ($documentInfo->getMetadataInstance()->isDirty()) {
                $dirty = true;
            }
            foreach ($documentInfo->getMetadataInstance() as $key => $value) {
                if (($value == null) || (($value instanceof MetadataAsDictionary) && ($value->isDirty()))) {
                    $dirty = true;
                }

                $documentInfo->getMetadata()[$key] = $mapper->normalize($value);
            }
        }
        return $dirty;
    }

    protected function updateSessionAfterSaveChanges(BatchCommandResult $result): void
    {
        $returnedTransactionIndex = $result->getTransactionIndex();
        $this->documentStore->setLastTransactionIndex($this->getDatabaseName(), $returnedTransactionIndex);
        $this->sessionInfo->setLastClusterTransactionIndex($returnedTransactionIndex);
    }

    /**
     * @deprecated
     *
     * @throws IllegalArgumentException
     */
    protected function entityChanged(array $newObject, DocumentInfo $documentInfo, array &$changes): bool
    {
        return JsonOperation::entityChanged($newObject, $documentInfo, $changes);
    }

    /**
     * @throws IllegalArgumentException
     */
    protected function getEntityChanges(array $newObject, DocumentInfo $documentInfo): DocumentsChangesArray
    {
        return JsonOperation::getEntityChanges($newObject, $documentInfo);
    }

    /**
     * @throws IllegalArgumentException
     */
    protected function isEntityChanged(array $newObject, DocumentInfo $documentInfo): bool
    {
        return JsonOperation::isEntityChanged($newObject, $documentInfo);
    }

    protected abstract function hasClusterSession(): bool;

    protected abstract function clearClusterSession(): void;

    public abstract function getClusterSession(): ClusterTransactionOperationsBase;

    private static function throwInvalidModifiedDocumentWithDefferedCommand(CommandDataInterface $resultCommand)
    {
        throw new IllegalStateException("Cannot perform save because document "
            . $resultCommand->getId()
            . " has been modified by the session and is also taking part in deferred "
            . $resultCommand->getType()
            . " command");
    }

    private static function throwInvalidModifiedDocumentWithDeferredCommand(CommandDataInterface $resultCommand): void
    {
        throw new IllegalStateException(
            "Cannot perform save because document "
            . $resultCommand->getId()
            . " has been modified by the session and is also taking part in deferred "
            . $resultCommand->getType()
            . " command"
        );
    }

    private static function throwInvalidDeletedDocumentWithDeferredCommand(CommandDataInterface $resultCommand): void
    {
        throw new IllegalStateException(
            "Cannot perform save because document "
            . $resultCommand->getId()
            . " has been deleted by the session and is also taking part in deferred "
            . $resultCommand->getType()
            . " command"
        );
}

    private static function throwNoDatabase(): void
    {
        throw new IllegalStateException(
            "Cannot open a Session without specifying a name of a database "
            . "to operate on. Database name can be passed as an argument when Session is"
            . " being opened or default database can be defined using 'DocumentStore.setDatabase()' method"
        );
    }

    public function onAfterSaveChangesInvoke(AfterSaveChangesEventArgs $eventArgs): void
    {
        EventHelper::invoke($this->onAfterSaveChanges, $this, $eventArgs);
    }

    public function onBeforeDeleteInvoke(BeforeDeleteEventArgs $eventArgs): void {
        EventHelper::invoke($this->onBeforeDelete, $this, $eventArgs);
    }

    public function onBeforeQueryInvoke(BeforeQueryEventArgs $eventArgs): void {
        EventHelper::invoke($this->onBeforeQuery, $this, $eventArgs);
    }

    public function onBeforeConversionToDocumentInvoke(string $id, object $entity): void
    {
        EventHelper::invoke(
            $this->onBeforeConversionToDocument,
            $this,
            new BeforeConversionToDocumentEventArgs($this, $id, $entity)
        );
    }

    public function validateClusterTransaction(SaveChangesData $result): void
    {
        // @todo: implement this
    }

//    public function onAfterConversionToDocumentInvoke(string $id, object $entity, Reference<ObjectNode> $document): void
//    {
//        if (!onAfterConversionToDocument.isEmpty()) {
//            AfterConversionToDocumentEventArgs eventArgs = new AfterConversionToDocumentEventArgs(this, id, entity, document);
//            EventHelper.invoke(onAfterConversionToDocument, this, eventArgs);
//
//            if (eventArgs.getDocument().value != null && eventArgs.getDocument().value != document.value) {
//                document.value = eventArgs.getDocument().value;
//            }
//        }
//    }
//
//
//      public void onBeforeConversionToEntityInvoke(String id, Class clazz, Reference<ObjectNode> document) {
//        if (!onBeforeConversionToEntity.isEmpty()) {
//            BeforeConversionToEntityEventArgs eventArgs = new BeforeConversionToEntityEventArgs(this, id, clazz, document);
//            EventHelper.invoke(onBeforeConversionToEntity, this, eventArgs);
//
//            if (eventArgs.getDocument() != null && eventArgs.getDocument().value != document.value) {
//                document.value = eventArgs.getDocument().value;
//            }
//        }
//    }
//
//    public void onAfterConversionToEntityInvoke(String id, ObjectNode document, Object entity) {
//        AfterConversionToEntityEventArgs eventArgs = new AfterConversionToEntityEventArgs(this, id, document, entity);
//        EventHelper.invoke(onAfterConversionToEntity, this, eventArgs);
//    }

    /**
     * Gets the store identifier for this session.
     * The store identifier is the identifier for the particular RavenDB instance.
     *
     * @return string store identifier
     */
    public function storeIdentifier(): string
    {
        return $this->documentStore->getIdentifier() . ";" . $this->databaseName;
    }
}
