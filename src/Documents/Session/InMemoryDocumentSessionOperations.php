<?php

namespace RavenDB\Documents\Session;

use _PHPStan_76800bfb5\Nette\NotImplementedException;
use Ramsey\Uuid\UuidInterface;
use RavenDB\Constants\Metadata;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreBase;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Identity\GenerateEntityIdOnTheClient;
use RavenDB\Exceptions\Documents\Session\NonUniqueObjectException;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\RequestExecutor;
use RavenDB\Json\JsonOperation;
use RavenDB\Json\MetadataAsDictionary;
use RavenDB\primitives\CleanCloseable;
use RavenDB\Utils\StringUtils;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use function PHPUnit\Framework\isEmpty;

abstract class InMemoryDocumentSessionOperations implements CleanCloseable
{
    protected bool $isDisposed = false;

    protected UuidInterface $id;
    protected string $databaseName;

    protected DocumentStoreBase $documentStore;
    protected RequestExecutor $requestExecutor;
    public bool $noTracking;
    protected TransactionMode $transactionMode;
    protected bool $disableAtomicDocumentWritesInClusterWideTransaction;

    protected SessionInfo $sessionInfo;

    protected int $numberOfRequests = 0;
    protected int $maxNumberOfRequestsPerSession;
    protected bool $useOptimisticConcurrency;

    protected array $knownMissingIds = [];

    protected bool $generateDocumentKeysOnStore = true;

    public DocumentsById $documentsById;
    public DocumentInfoArray $includedDocumentsById;

    private GenerateEntityIdOnTheClient $generateEntityIdOnTheClient;

    private EntityToJson $entityToJson;

    public DocumentsByEntityHolder $documentsByEntity;

    abstract protected function generateId(?object $entity): string;

    protected array $deferredCommandsMap = [];

    /**
     * @throws IllegalStateException
     * @throws IllegalArgumentException
     */
    public function __construct(DocumentStoreBase $documentStore, UuidInterface $id, SessionOptions $options)
    {
        $this->documentsByEntity = new DocumentsByEntityHolder();

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

//
//        this.useOptimisticConcurrency = _requestExecutor.getConventions().isUseOptimisticConcurrency();
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

    public function checkIfIdAlreadyIncluded(array $ids, array $includes): bool
    {
        foreach ($ids as $id) {
            if (in_array($id, $this->knownMissingIds)) {
                continue;
            }


//
//            // Check if document was already loaded, the check if we've received it through include
//            DocumentInfo documentInfo = documentsById.getValue(id);
//            if (documentInfo == null) {
//                documentInfo = includedDocumentsById.get(id);
//                if (documentInfo == null) {
//                    return false;
//                }
//            }
//
//            if (documentInfo.getEntity() == null && documentInfo.getDocument() == null) {
//                return false;
//            }
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

    public function registerMissing(array $ids): void
    {
        if ($this->noTracking) {
            return;
        }

        $this->knownMissingIds = array_merge($this->knownMissingIds, $ids);
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
     * @throws IllegalStateException
     * @throws IllegalArgumentException
     * @throws NonUniqueObjectException
     */
    public function store(?object $entity, ?string $id = null, ?string $changeVector = null): void
    {
        $concurrencyCheckMode = ConcurrencyCheckMode::auto();

        if ($id == null) {
            if ($this->generateEntityIdOnTheClient->entityHasId($entity)) {
                $concurrencyCheckMode = ConcurrencyCheckMode::forced();
            }
        }

        $this->storeInternal($entity, $id, $changeVector, $concurrencyCheckMode);
    }

    /**
     * @throws IllegalStateException
     * @throws IllegalArgumentException
     * @throws NonUniqueObjectException
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
            throw new IllegalArgumentException("Entity cannot be null");
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

//        if (deferredCommandsMap.containsKey(IdTypeAndName.create(id, CommandType.CLIENT_ANY_COMMAND, null))) {
//            throw new IllegalStateException(
//              "Can't store document, there is a deferred command registered for this document in the session." .
//              " Document id: " + id
//            );
//        }
//
//        if (deletedEntities.contains(entity)) {
//            throw new IllegalStateException(
//              "Can't store object, it was already deleted in this session. Document id: " + id
//            );
//        }


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

        // I've added this because change vector must be string, but it's never set on crudTest...
        $documentInfo->setChangeVector($changeVector ?? ''); // @todo: remove this: ?? ''
        //
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

    public function whatChanged(): array
    {
        $changes = $this->getAllEntitiesChanges();

//        prepareForEntitiesDeletion(null, $changes);

        return $changes;
    }

    private function getAllEntitiesChanges(): array
    {
        // @todo: implement this
        foreach ($this->documentsById as $id => $documentInfo) {
            $this->updateMetadataModifications($documentInfo);
            $newObj = $this->entityToJson->convertEntityToJson($documentInfo->getEntity(), $documentInfo);
            $this->entityChanged($newObj, $documentInfo, $changes);
        }

//        for (Map.Entry<String, DocumentInfo> pair : documentsById) {
//            updateMetadataModifications(pair.getValue());
//            ObjectNode newObj = entityToJson.convertEntityToJson(pair.getValue().getEntity(), pair.getValue());
//            entityChanged(newObj, pair.getValue(), changes);
//        }

        return [];
    }

    private function prepareForEntitiesDeletion(?SaveChangesData $result, array $changes): void
    {
        // @todo: implement this

//        try (CleanCloseable deletes = deletedEntities.prepareEntitiesDeletes()) {
//
//            for (DeletedEntitiesHolder.DeletedEntitiesEnumeratorResult deletedEntity : deletedEntities) {
//                DocumentInfo documentInfo = documentsByEntity.get(deletedEntity.entity);
//                if (documentInfo == null) {
//                    continue;
//                }
//
//if (changes != null) {
//    List<DocumentsChanges> docChanges = new ArrayList<>();
//                    DocumentsChanges change = new DocumentsChanges();
//                    change.setFieldNewValue("");
//                    change.setFieldOldValue("");
//                    change.setChange(DocumentsChanges.ChangeType.DOCUMENT_DELETED);
//
//                    docChanges.add(change);
//                    changes.put(documentInfo.getId(), docChanges);
//                } else {
//    ICommandData command =
//          result.getDeferredCommandsMap().get(IdTypeAndName.create(
//                          documentInfo.getId(),
//                          CommandType.CLIENT_ANY_COMMAND, null
//                      ));
//                    if (command != null) {
//                        throwInvalidDeletedDocumentWithDeferredCommand(command);
//                    }
//
//                    String changeVector = null;
//                    documentInfo = documentsById.getValue(documentInfo.getId());
//
//                    if (documentInfo != null) {
//                        changeVector = documentInfo.getChangeVector();
//
//                        if (documentInfo.getEntity() != null) {
//                            result.onSuccess.removeDocumentByEntity(documentInfo.getEntity());
//                            result.getEntities().add(documentInfo.getEntity());
//                        }
//
//                        result.onSuccess.removeDocumentByEntity(documentInfo.getId());
//                    }
//
//                    if (!useOptimisticConcurrency) {
//                        changeVector = null;
//                    }
//
//                    onBeforeDeleteInvoke(
//                        new BeforeDeleteEventArgs(this, documentInfo.getId(), documentInfo.getEntity())
//                    );
//                    DeleteCommandData deleteCommandData =
//                          new DeleteCommandData(documentInfo.getId(), changeVector, documentInfo.getChangeVector());
//                    result.getSessionCommands().add(deleteCommandData);
//                }
//
//if (changes == null) {
//    result.onSuccess.clearDeletedEntities();
//}
//}
//}
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

    protected function entityChanged(array $newObject, DocumentInfo $documentInfo, array $changes): bool
    {
        return JsonOperation::entityChanged($newObject, $documentInfo, $changes);
    }
}
