<?php

namespace RavenDB\Documents\Session;

use Closure;
use InvalidArgumentException;
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
use RavenDB\Http\ServerNode;
use RavenDB\Json\BatchCommandResult;
use RavenDB\Json\JsonOperation;
use RavenDB\Json\MetadataAsDictionary;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Primitives\ClosureArray;
use RavenDB\Primitives\EventHelper;
use RavenDB\Type\StringArray;
use RavenDB\Utils\AtomicInteger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

use DS\Map as DSMap;

// !status: copied complete code as in the original java file
abstract class InMemoryDocumentSessionOperations implements CleanCloseable
{
    protected RequestExecutor $requestExecutor;

//    private OperationExecutor _operationExecutor;

//    protected final List<ILazyOperation> pendingLazyOperations = new ArrayList<>();
//    protected final Map<ILazyOperation, Consumer<Object>> onEvaluateLazy = new HashMap<>();
//
    private static  ?AtomicInteger $instancesCounter = null;

    private int $hash = 0;
    protected bool $generateDocumentKeysOnStore = true;
    protected SessionInfo $sessionInfo;
    public ?BatchOptions $saveChangesOptions = null;

    public bool $disableAtomicDocumentWritesInClusterWideTransaction;

    protected TransactionMode $transactionMode;

    protected bool $isDisposed = false;

//    protected final ObjectMapper mapper = JsonExtensions.getDefaultMapper();

    protected UuidInterface $id;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    private ClosureArray $onBeforeStore;
    private ClosureArray $onAfterSaveChanges;
    private ClosureArray $onBeforeDelete;
    private ClosureArray $onBeforeQuery;

    private ClosureArray $onBeforeConversionToDocument;
    private ClosureArray $onAfterConversionToDocument;
    private ClosureArray $onBeforeConversionToEntity;
    private ClosureArray $onAfterConversionToEntity;

    private ClosureArray $onSessionClosing;

    public function addBeforeStoreListener(Closure $handler): void
    {
        $this->onBeforeStore->append($handler);
    }

    public function removeBeforeStoreListener(Closure $handler): void
    {
        $this->onBeforeStore->removeValue($handler);
    }

    public function addAfterSaveChangesListener(Closure $handler): void
    {
        $this->onAfterSaveChanges->append($handler);
    }

    public function removeAfterSaveChangesListener(Closure $handler): void
    {
        $this->onAfterSaveChanges->removeValue($handler);
    }

    public function addBeforeDeleteListener(Closure $handler): void
    {
        $this->onBeforeDelete->append($handler);
    }

    public function removeBeforeDeleteListener(Closure $handler): void
    {
        $this->onBeforeDelete->removeValue($handler);
    }

    public function addBeforeQueryListener(Closure $handler): void
    {
        $this->onBeforeQuery->append($handler);
    }

    public function removeBeforeQueryListener(Closure $handler): void
    {
        $this->onBeforeQuery->removeValue($handler);
    }

    public function addBeforeConversionToDocumentListener(Closure $handler): void
    {
        $this->onBeforeConversionToDocument->append($handler);
    }

    public function removeBeforeConversionToDocumentListener(Closure $handler): void
    {
        $this->onBeforeConversionToDocument->removeValue($handler);
    }

    public function addAfterConversionToDocumentListener(Closure $handler) {
        $this->onAfterConversionToDocument->append($handler);
    }

    public function removeAfterConversionToDocumentListener(Closure $handler) {
        $this->onAfterConversionToDocument->removeValue($handler);
    }

    public function addBeforeConversionToEntityListener(Closure $handler) {
        $this->onBeforeConversionToEntity->append($handler);
    }

    public function removeBeforeConversionToEntityListener(Closure $handler) {
        $this->onBeforeConversionToEntity->removeValue($handler);
    }

    public function addAfterConversionToEntityListener(Closure $handler) {
        $this->onAfterConversionToEntity->append($handler);
    }

    public function removeAfterConversionToEntityListener(Closure $handler) {
        $this->onAfterConversionToEntity->removeValue($handler);
    }

    public function addOnSessionClosingListener(Closure $handler) {
        $this->onSessionClosing->append($handler);
    }

    public function removeOnSessionClosingListener(Closure $handler) {
        $this->onSessionClosing->removeValue($handler);
    }

    // @todo: This should be set of strings / not array !!! - fix this in future (now it's working like this)

    //Entities whose id we already know do not exists, because they are a missing include, or a missing load, etc.
    protected array $knownMissingIds = [];

//    private Map<String, Object> externalState;
//
//    public Map<String, Object> getExternalState() {
//        if (externalState == null) {
//            externalState = new HashMap<>();
//        }
//        return externalState;
//    }

    public function getCurrentSessionNode(): ServerNode
    {
        return $this->getSessionInfo()->getCurrentSessionNode($this->requestExecutor);
    }

    /**
     * Translate between an ID and its associated entity
     */
    public DocumentsById $documentsById;

    /**
     * Translate between an ID and its associated entity
     */
    public DocumentInfoArray $includedDocumentsById;

    /**
     * hold the data required to manage the data for RavenDB's Unit of Work
     */
    public DocumentsByEntityHolder $documentsByEntity;

    /**
     * The entities waiting to be deleted
     */
    public DeletedEntitiesHolder $deletedEntities;

//    /**
//     * @return map which holds the data required to manage Counters tracking for RavenDB's Unit of Work
//     */
//    public Map<String, Tuple<Boolean, Map<String, Long>>> getCountersByDocId() {
//        if (_countersByDocId == null) {
//            _countersByDocId = new TreeMap<>(String::compareToIgnoreCase);
//        }
//        return _countersByDocId;
//    }

    // @todo: Change from array to adequate format
//    private Map<String, Tuple<Boolean, Map<String, Long>>> _countersByDocId;
    private array $countersByDocId = [];

    // @todo: Change from array to adequate format
//    private Map<String, Map<String, List<TimeSeriesRangeResult>>> _timeSeriesByDocId;
    private array $timeSeriesByDocId = [];

//    public Map<String, Map<String, List<TimeSeriesRangeResult>>> getTimeSeriesByDocId() {
//        if (_timeSeriesByDocId == null) {
//            _timeSeriesByDocId = new TreeMap<>(String::compareToIgnoreCase);
//        }
//
//        return _timeSeriesByDocId;
//    }

    protected DocumentStoreBase $documentStore;

    protected string $databaseName;

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * The document store associated with this session
     */
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

//        public OperationExecutor getOperations() {
//        if (_operationExecutor == null) {
//            _operationExecutor = new SessionOperationExecutor(this);
//        }
//
//        return _operationExecutor;
//    }

    private int $numberOfRequests = 0;

    public function getNumberOfRequests(): int
    {
        return $this->numberOfRequests;
    }

//    /**
//     * Gets the number of entities held in memory to manage Unit of Work
//     *
//     * @return number of entities held in memory
//     */
//    public int getNumberOfEntitiesInUnitOfWork() {
//        return documentsByEntity.size();
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

//    /**
//     * Gets the conventions used by this session
//     * This instance is shared among all sessions, changes to the DocumentConventions should be done
//     * via the IDocumentSTore instance, not on a single session.
//     *
//     * @return document conventions
//     */
    public function getConventions(): DocumentConventions
    {
        return $this->requestExecutor->getConventions();
    }

    protected int $maxNumberOfRequestsPerSession;

    /**
     * Gets the max number of requests per session.
     * If the NumberOfRequests rise above MaxNumberOfRequestsPerSession, an exception will be thrown.
     *
     * @return int maximum number of requests per session
     */
    public function getMaxNumberOfRequestsPerSession(): int
    {
        return $this->maxNumberOfRequestsPerSession;
    }

    /**
     * Sets the max number of requests per session.
     * If the NumberOfRequests rise above MaxNumberOfRequestsPerSession, an exception will be thrown.
     *
     * @param int $maxNumberOfRequestsPerSession sets the value
     */
    public function setMaxNumberOfRequestsPerSession(int $maxNumberOfRequestsPerSession): void
    {
        $this->maxNumberOfRequestsPerSession = $maxNumberOfRequestsPerSession;
    }

    protected bool $useOptimisticConcurrency = false;

    /**
     * Gets value indicating whether the session should use optimistic concurrency.
     * When set to true, a check is made so that a change made behind the session back would fail
     * and raise ConcurrencyException
     *
     * @return bool true if optimistic concurrency should be used
     */
    public function isUseOptimisticConcurrency(): bool
    {
        return $this->useOptimisticConcurrency;
    }

    /**
     * Sets value indicating whether the session should use optimistic concurrency.
     * When set to true, a check is made so that a change made behind the session back would fail
     * and raise ConcurrencyException
     *
     * @param bool $useOptimisticConcurrency sets the value
     */
    public function setUseOptimisticConcurrency(bool $useOptimisticConcurrency): void
    {
        $this->useOptimisticConcurrency = $useOptimisticConcurrency;
    }

    // @todo: CommandDataArray
//    protected final List<ICommandData> deferredCommands = new ArrayList<>();
    public array $deferredCommands = [];

    // @todo: update type here
//    final Map<IdTypeAndName, ICommandData> deferredCommandsMap = new HashMap<>();
    public ?DSMap $deferredCommandsMap = null;

    public bool $noTracking;

    // @todo: update type here to ForceRevisionStrategyArray
//    public Map<String, ForceRevisionStrategy> idsForCreatingForcedRevisions = new TreeMap<>(String::compareToIgnoreCase);
    public array $idsForCreatingForcedRevisions = [];

    public function getDeferredCommandsCount(): int
    {
        return count($this->deferredCommands);
    }

    private GenerateEntityIdOnTheClient $generateEntityIdOnTheClient;

    public function getGenerateEntityIdOnTheClient(): GenerateEntityIdOnTheClient
    {
        return $this->generateEntityIdOnTheClient;
    }

    private EntityToJson $entityToJson;

    public function getEntityToJson(): EntityToJson
    {
        return $this->entityToJson;
    }

    /**
     * Initializes a new instance of the InMemoryDocumentSessionOperations class.
     *
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     */
    public function __construct(DocumentStoreBase $documentStore, UuidInterface $id, SessionOptions $options)
    {
        if (self::$instancesCounter == null) {
            self::$instancesCounter = new AtomicInteger(0);
        }
        $this->hash = self::$instancesCounter->incrementAndGet();

        $this->onBeforeStore = new ClosureArray();
        $this->onAfterSaveChanges = new ClosureArray();
        $this->onBeforeDelete = new ClosureArray();
        $this->onBeforeQuery = new ClosureArray();

        $this->onBeforeConversionToDocument = new ClosureArray();
        $this->onAfterConversionToDocument = new ClosureArray();
        $this->onBeforeConversionToEntity = new ClosureArray();
        $this->onAfterConversionToEntity = new ClosureArray();

        $this->onSessionClosing = new ClosureArray();

        $this->documentsByEntity = new DocumentsByEntityHolder();
        $this->deletedEntities = new DeletedEntitiesHolder();

        $this->documentsById = new DocumentsById();
        $this->includedDocumentsById = new DocumentInfoArray();

        $this->deferredCommands = [];
        $this->deferredCommandsMap = new DSMap();

        $this->entityToJson = new EntityToJson($this);

        //-- Init

        $this->id = $id;

        $this->databaseName = $options->getDatabase() ?? $documentStore->getDatabase();

        if (empty($this->databaseName)) {
            $this->throwNoDatabase();
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
    }

    /**
     * Gets the metadata for the specified entity.
     */
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

//    /**
//     * Gets all counter names for the specified entity.
//     * @param instance Instance
//     * @param <T> Instance class
//     * @return All counters names
//     */
//    public <T> List<String> getCountersFor(T instance) {
//        if (instance == null) {
//            throw new IllegalArgumentException("Instance cannot be null");
//        }
//
//        DocumentInfo documentInfo = getDocumentInfo(instance);
//
//        ArrayNode countersArray = (ArrayNode) documentInfo.getMetadata().get(Constants.Documents.Metadata.COUNTERS);
//        if (countersArray == null) {
//            return null;
//        }
//
//        return IntStream.range(0, countersArray.size())
//                .mapToObj(i -> countersArray.get(i).asText())
//                .collect(Collectors.toList());
//    }
//
//    /**
//     * Gets all time series names for the specified entity.
//     * @param instance Entity
//     * @param <T> Entity class
//     * @return time series names
//     */
//    public <T> List<String> getTimeSeriesFor(T instance) {
//        if (instance == null) {
//            throw new IllegalArgumentException("Instance cannot be null");
//        }
//
//        DocumentInfo documentInfo = getDocumentInfo(instance);
//
//        JsonNode array = documentInfo.getMetadata().get(Constants.Documents.Metadata.TIME_SERIES);
//        if (array == null) {
//            return Collections.emptyList();
//        }
//
//        ArrayNode bjra = (ArrayNode) array;
//
//        List<String> tsList = new ArrayList<>(bjra.size());
//
//        for (JsonNode jsonNode : bjra) {
//            tsList.add(jsonNode.asText());
//        }
//
//        return tsList;
//    }

//    /**
//     * Gets the Change Vector for the specified entity.
//     * If the entity is transient, it will load the change vector from the store
//     * and associate the current state of the entity with the change vector from the server.
//     *
//     * @param <T>      instance class
//     * @param instance Instance to get change vector from
//     * @return change vector
//     */
//    public <T> String getChangeVectorFor(T instance) {
//        if (instance == null) {
//            throw new IllegalArgumentException("instance cannot be null");
//        }
//
//        DocumentInfo documentInfo = getDocumentInfo(instance);
//        JsonNode changeVector = documentInfo.getMetadata().get(Constants.Documents.Metadata.CHANGE_VECTOR);
//        if (changeVector != null) {
//            return changeVector.asText();
//        }
//        return null;
//    }
//
//    public <T> Date getLastModifiedFor(T instance) {
//        if (instance == null) {
//            throw new IllegalArgumentException("Instance cannot be null");
//        }
//
//        DocumentInfo documentInfo = getDocumentInfo(instance);
//        JsonNode lastModified = documentInfo.getMetadata().get(Constants.Documents.Metadata.LAST_MODIFIED);
//        if (lastModified != null && !lastModified.isNull()) {
//            return mapper.convertValue(lastModified, Date.class);
//        }
//        return null;
//    }

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

//    /**
//     * Returns whether a document with the specified id is loaded in the
//     * current session
//     *
//     * @param id Document id to check
//     * @return true is document is loaded
//     */
//    public boolean isLoaded(String id) {
//        return isLoadedOrDeleted(id);
//    }
//
//    public boolean isLoadedOrDeleted(String id) {
//        DocumentInfo documentInfo = documentsById.getValue(id);
//        return (documentInfo != null && (documentInfo.getDocument() != null || documentInfo.getEntity() != null)) || isDeleted(id) || includedDocumentsById.containsKey(id);
//    }

    /**
     * Returns whether a document with the specified id is deleted
     * or known to be missing
     *
     * @param id Document id to check
     * @return true is document is deleted
     */
    public function isDeleted(string $id): bool
    {
        return in_array($id, $this->knownMissingIds);
    }

//    /**
//     * Gets the document id.
//     *
//     * @param instance instance to get document id from
//     * @return document id
//     */
//    public String getDocumentId(Object instance) {
//        if (instance == null) {
//            return null;
//        }
//        DocumentInfo value = documentsByEntity.get(instance);
//        return value != null ? value.getId() : null;
//    }

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


    /**
     * Tracks the entity inside the unit of work
     *
     * @throws IllegalStateException
     * @throws ExceptionInterface
     */
    public function trackEntity(string $className, DocumentInfo $docFound): ?object
    {
        return $this->trackEntityInternal(
            $className,
            $docFound->getId(),
            $docFound->getDocument(),
            $docFound->getMetadata(),
            $this->noTracking
        );
    }

//    public void registerExternalLoadedIntoTheSession(DocumentInfo info) {
//        if (noTracking) {
//            return;
//        }
//
//        DocumentInfo existing = documentsById.getValue(info.getId());
//        if (existing != null) {
//            if (existing.getEntity() == info.getEntity()) {
//                return;
//            }
//
//            throw new IllegalStateException("The document " + info.getId() + " is already in the session with a different entity instance.");
//        }
//
//        DocumentInfo existingEntity = documentsByEntity.get(info.getEntity());
//        if (existingEntity != null) {
//            if (existingEntity.getId().equalsIgnoreCase(info.getId())) {
//                return;
//            }
//
//            throw new IllegalStateException("Attempted to load an entity with id " + info.getId() + ", but the entity instance already exists in the session with id: " + existing.getId());
//        }
//
//        documentsByEntity.put(info.getEntity(), info);
//        documentsById.add(info);
//        includedDocumentsById.remove(info.getId());
//
//    }

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

//    /**
//     * Gets the default value of the specified type.
//     *
//     * @param clazz Class
//     * @return Default value to given class
//     */
//    @SuppressWarnings("unchecked")
//    public static Object getDefaultValue(Class clazz) {
//        return Defaults.defaultValue(clazz);
//    }

    /**
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

    abstract protected function generateId(?object $entity): string;

    private function rememberEntityForDocumentIdGeneration(object $entity)
    {
        throw new NotImplementedException(
            "You cannot set GenerateDocumentIdsOnStore to false " .
            "without implementing RememberEntityForDocumentIdGeneration"
        );
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
        if (empty($id)
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

    // @todo: implement this
    public function validateClusterTransaction(SaveChangesData $result): void
    {
//        if (transactionMode != TransactionMode.CLUSTER_WIDE) {
//            return;
//        }
//
//        if (isUseOptimisticConcurrency()) {
//            throw new IllegalStateException("useOptimisticConcurrency is not supported with TransactionMode set to " + TransactionMode.CLUSTER_WIDE);
//        }
//
//        for (ICommandData commandData : result.getSessionCommands()) {
//
//            switch (commandData.getType()) {
//                case PUT:
//                case DELETE:
//                    if (commandData.getChangeVector() != null) {
//                        throw new IllegalStateException("Optimistic concurrency for " + commandData.getId() + " is not supported when using a cluster transaction");
//                    }
//                    break;
//                case COMPARE_EXCHANGE_DELETE:
//                case COMPARE_EXCHANGE_PUT:
//                    break;
//                default:
//                    throw new IllegalStateException("The command '" + commandData.getType() + "' is not supported in a cluster session.");
//            }
//        }
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

    protected abstract function hasClusterSession(): bool;

    protected abstract function clearClusterSession(): void;

    public abstract function getClusterSession(): ClusterTransactionOperationsBase;


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

    private function prepareForCreatingRevisionsFromIds(SaveChangesData $result): void
    {
        // Note: here there is no point checking 'Before' or 'After' because if there were changes then forced revision is done from the PUT command....

        foreach (array_keys($this->idsForCreatingForcedRevisions) as $idEntry) {
            $result->addSessionCommand(new ForceRevisionCommandData($idEntry));
        }

        $this->idsForCreatingForcedRevisions = [];
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
                    $this->throwInvalidModifiedDocumentWithDeferredCommand($command);
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

//    /**
//     * Gets a value indicating whether any of the entities tracked by the session has changes.
//     *
//     * @return true if session has changes
//     */
//    public boolean hasChanges() {
//        for (DocumentsByEntityHolder.DocumentsByEntityEnumeratorResult entity : documentsByEntity) {
//            ObjectNode document = entityToJson.convertEntityToJson(entity.getKey(), entity.getValue());
//            if (entityChanged(document, entity.getValue(), null)) {
//                return true;
//            }
//
//        }
//        return !deletedEntities.isEmpty();
//    }

//  /**
//     * Determines whether the specified entity has changed.
//     *
//     * @param entity Entity to check
//     * @return true if entity has changed
//     */
//    public boolean hasChanged(Object entity) {
//        DocumentInfo documentInfo = documentsByEntity.get(entity);
//
//        if (documentInfo == null) {
//            return false;
//        }
//
//        ObjectNode document = entityToJson.convertEntityToJson(entity, documentInfo);
//        return entityChanged(document, documentInfo, null);
//    }

//    public void waitForReplicationAfterSaveChanges() {
//        waitForReplicationAfterSaveChanges(options -> {
//        });
//    }
//
//    public void waitForReplicationAfterSaveChanges(Consumer<ReplicationWaitOptsBuilder> options) {
//        ReplicationWaitOptsBuilder builder = new ReplicationWaitOptsBuilder();
//        options.accept(builder);
//
//        BatchOptions builderOptions = builder.getOptions();
//        ReplicationBatchOptions replicationOptions = builderOptions.getReplicationOptions();
//        if (replicationOptions == null) {
//            builderOptions.setReplicationOptions(replicationOptions = new ReplicationBatchOptions());
//        }
//
//        if (replicationOptions.getWaitForReplicasTimeout() == null) {
//            replicationOptions.setWaitForReplicasTimeout(getConventions().getWaitForReplicationAfterSaveChangesTimeout());
//        }
//
//        replicationOptions.setWaitForReplicas(true);
//    }

//    public void waitForIndexesAfterSaveChanges() {
//        waitForIndexesAfterSaveChanges(options -> {
//        });
//    }
//
//    public void waitForIndexesAfterSaveChanges(Consumer<InMemoryDocumentSessionOperations.IndexesWaitOptsBuilder> options) {
//        IndexesWaitOptsBuilder builder = new IndexesWaitOptsBuilder();
//        options.accept(builder);
//
//        BatchOptions builderOptions = builder.getOptions();
//        IndexBatchOptions indexOptions = builderOptions.getIndexOptions();
//
//        if (indexOptions == null) {
//            builderOptions.setIndexOptions(indexOptions = new IndexBatchOptions());
//        }
//
//        if (indexOptions.getWaitForIndexesTimeout() == null) {
//            indexOptions.setWaitForIndexesTimeout(getConventions().getWaitForIndexesAfterSaveChangesTimeout());
//        }
//
//        indexOptions.setWaitForIndexes(true);
//    }

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

//    /**
//     * Mark the entity as one that should be ignore for change tracking purposes,
//     * it still takes part in the session, but is ignored for SaveChanges.
//     *
//     * @param entity entity
//     */
//    public void ignoreChangesFor(Object entity) {
//        getDocumentInfo(entity).setIgnoreChanges(true);
//    }

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

    // @todo: change this deffers to single call

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

    public function close(bool $isDisposing = true): void
    {
        if ($this->isDisposed) {
            return;
        }

        // todo: implement this
//        EventHelper.invoke(onSessionClosing, this, new SessionClosingEventArgs(this));

        $this->isDisposed = true;

        // nothing more to do for now
    }

    /**
     * @param string|StringArray $ids one or more IDs that are missing
     */
    public function registerMissing($ids): void
    {
        if (is_string($ids)) {
            $this->_registerMissingId($ids);
            return;
        }

        $this->_registerMissingIds($ids);
    }

    private function _registerMissingId(string $id): void
    {
        if ($this->noTracking) {
            return;
        }

        $this->knownMissingIds[] = $id;
    }

    private function _registerMissingIds(StringArray $ids): void
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

//    public void registerCounters(ObjectNode resultCounters, String[] ids, String[] countersToInclude, boolean gotAll) {
//        if (noTracking) {
//            return;
//        }
//
//        if (resultCounters == null || resultCounters.size() == 0) {
//            if (gotAll) {
//                for (String id : ids) {
//                    setGotAllCountersForDocument(id);
//                }
//
//                return;
//            }
//        } else {
//            registerCountersInternal(resultCounters, null, false, gotAll);
//        }
//
//        registerMissingCounters(ids, countersToInclude);
//    }
//
//    public void registerCounters(ObjectNode resultCounters, Map<String, String[]> countersToInclude) {
//        if (noTracking) {
//            return;
//        }
//
//        if (resultCounters == null || resultCounters.size() == 0) {
//            setGotAllInCacheIfNeeded(countersToInclude);
//        } else {
//            registerCountersInternal(resultCounters, countersToInclude, true, false);
//        }
//
//        registerMissingCounters(countersToInclude);
//    }

//    private void registerCountersInternal(ObjectNode resultCounters, Map<String, String[]> countersToInclude, boolean fromQueryResult, boolean gotAll) {
//
//        Iterator<Map.Entry<String, JsonNode>> fieldsIterator = resultCounters.fields();
//
//        while (fieldsIterator.hasNext()) {
//            Map.Entry<String, JsonNode> fieldAndValue = fieldsIterator.next();
//
//            if (fieldAndValue.getValue() == null || fieldAndValue.getValue().isNull()) {
//                continue;
//            }
//
//            String[] counters = new String[0];
//
//            if (fromQueryResult) {
//                counters = countersToInclude.get(fieldAndValue.getKey());
//                gotAll = counters != null && counters.length == 0;
//            }
//
//            if (fieldAndValue.getValue().size() == 0 && !gotAll) {
//                Tuple<Boolean, Map<String, Long>> cache =
//                        _countersByDocId.get(fieldAndValue.getKey());
//                if (cache == null) {
//                    continue;
//                }
//
//                for (String counter : counters) {
//                    cache.second.remove(counter);
//                }
//
//                _countersByDocId.put(fieldAndValue.getKey(), cache);
//                continue;
//            }
//
//            registerCountersForDocument(fieldAndValue.getKey(), gotAll, (ArrayNode) fieldAndValue.getValue(), countersToInclude);
//        }
//    }

//    private void registerCountersForDocument(String id, boolean gotAll, ArrayNode counters, Map<String, String[]> countersToInclude) {
//        Tuple<Boolean, Map<String, Long>> cache = getCountersByDocId().get(id);
//        if (cache == null) {
//            cache = Tuple.create(gotAll, new TreeMap<>(String::compareToIgnoreCase));
//        }
//
//        Set<String> deletedCounters = cache.second.isEmpty()
//                ? new HashSet<>()
//                : (countersToInclude.get(id).length == 0
//                    ? new HashSet<>(cache.second.keySet())
//                    : new HashSet<>(Arrays.asList(countersToInclude.get(id))));
//
//        for (JsonNode counterJson : counters) {
//            JsonNode counterName = counterJson.get("CounterName");
//            JsonNode totalValue = counterJson.get("TotalValue");
//
//            if (counterName != null && !counterName.isNull() && totalValue != null && !totalValue.isNull()) {
//                cache.second.put(counterName.asText(), totalValue.longValue());
//                deletedCounters.remove(counterName.asText());
//            }
//        }
//
//        if (!deletedCounters.isEmpty()) {
//            for (String name : deletedCounters) {
//                cache.second.remove(name);
//            }
//        }
//
//        cache.first = gotAll;
//        getCountersByDocId().put(id, cache);
//    }
//
//    private void setGotAllInCacheIfNeeded(Map<String, String[]> countersToInclude) {
//        for (Map.Entry<String, String[]> kvp : countersToInclude.entrySet()) {
//            if (kvp.getValue().length > 0) {
//                continue;
//            }
//
//            setGotAllCountersForDocument(kvp.getKey());
//        }
//    }
//
//    private void setGotAllCountersForDocument(String id) {
//        Tuple<Boolean, Map<String, Long>> cache = getCountersByDocId().get(id);
//
//        if (cache == null) {
//            cache = Tuple.create(false, new TreeMap<>(String::compareToIgnoreCase));
//        }
//
//        cache.first = true;
//        getCountersByDocId().put(id, cache);
//    }
//
//    private void registerMissingCounters(Map<String, String[]> countersToInclude) {
//        if (countersToInclude == null) {
//            return;
//        }
//
//        for (Map.Entry<String, String[]> kvp : countersToInclude.entrySet()) {
//            Tuple<Boolean, Map<String, Long>> cache = getCountersByDocId().get(kvp.getKey());
//            if (cache == null) {
//                cache = Tuple.create(false, new TreeMap<>(String::compareToIgnoreCase));
//                getCountersByDocId().put(kvp.getKey(), cache);
//            }
//
//            for (String counter : kvp.getValue()) {
//                if (cache.second.containsKey(counter)) {
//                    continue;
//                }
//
//                cache.second.put(counter, null);
//            }
//        }
//    }
//
//    private void registerMissingCounters(String[] ids, String[] countersToInclude) {
//        if (countersToInclude == null) {
//            return;
//        }
//
//        for (String counter : countersToInclude) {
//            for (String id : ids) {
//                Tuple<Boolean, Map<String, Long>> cache = getCountersByDocId().get(id);
//                if (cache == null) {
//                    cache = Tuple.create(false, new TreeMap<>(String::compareToIgnoreCase));
//                    getCountersByDocId().put(id, cache);
//                }
//
//                if (cache.second.containsKey(counter)) {
//                    continue;
//                }
//
//                cache.second.put(counter, null);
//            }
//        }
//    }


        // @todo: implement this
    public function registerTimeSeries(array $resultTimeSeries): void
    {
    }

//    public void registerTimeSeries(ObjectNode resultTimeSeries) {
//        if (noTracking || resultTimeSeries == null) {
//            return;
//        }
//
//        Iterator<Map.Entry<String, JsonNode>> fields = resultTimeSeries.fields();
//        while (fields.hasNext()) {
//            Map.Entry<String, JsonNode> field = fields.next();
//            if (field.getValue() == null || field.getValue().isNull()) {
//                continue;
//            }
//
//            String id = field.getKey();
//
//            Map<String, List<TimeSeriesRangeResult>> cache =
//                    getTimeSeriesByDocId().computeIfAbsent(id, x -> new TreeMap<>(String::compareToIgnoreCase));
//
//            if (!field.getValue().isObject()) {
//                throw new IllegalStateException("Unable to read time series range results on document: '" + id + "'.");
//            }
//
//            Iterator<Map.Entry<String, JsonNode>> innerFields = field.getValue().fields();
//
//            while (innerFields.hasNext()) {
//                Map.Entry<String, JsonNode> innerField = innerFields.next();
//
//                if (innerField.getValue() == null || innerField.getValue().isNull()) {
//                    continue;
//                }
//
//                String name = innerField.getKey();
//
//                if (!innerField.getValue().isArray()) {
//                    throw new IllegalStateException("Unable to read time series range results on document: '" + id + "', time series: '" + name + "'.");
//                }
//
//                for (JsonNode jsonRange : innerField.getValue()) {
//                    TimeSeriesRangeResult newRange = parseTimeSeriesRangeResult(mapper, (ObjectNode) jsonRange, id, name);
//                    addToCache(cache, newRange, name);
//                }
//            }
//        }
//    }
//
//    private static void addToCache(Map<String, List<TimeSeriesRangeResult>> cache,
//                                   TimeSeriesRangeResult newRange,
//                                   String name) {
//        List<TimeSeriesRangeResult> localRanges = cache.get(name);
//        if (localRanges == null || localRanges.isEmpty()) {
//            // no local ranges in cache for this series
//
//            List<TimeSeriesRangeResult> item = new ArrayList<>();
//            item.add(newRange);
//            cache.put(name, item);
//            return;
//        }
//
//        if (DatesComparator.compare(leftDate(localRanges.get(0).getFrom()), rightDate(newRange.getTo())) > 0
//                || DatesComparator.compare(rightDate(localRanges.get(localRanges.size() - 1).getTo()), leftDate(newRange.getFrom())) < 0) {
//            // the entire range [from, to] is out of cache bounds
//
//            int index = DatesComparator.compare(leftDate(localRanges.get(0).getFrom()), rightDate(newRange.getTo())) > 0 ? 0 : localRanges.size();
//            localRanges.add(index, newRange);
//            return;
//        }
//
//        int toRangeIndex;
//        int fromRangeIndex = -1;
//        boolean rangeAlreadyInCache = false;
//
//        for (toRangeIndex = 0; toRangeIndex < localRanges.size(); toRangeIndex++) {
//            if (DatesComparator.compare(leftDate(localRanges.get(toRangeIndex).getFrom()), leftDate(newRange.getFrom())) <= 0) {
//                if (DatesComparator.compare(rightDate(localRanges.get(toRangeIndex).getTo()), rightDate(newRange.getTo())) >= 0) {
//                    rangeAlreadyInCache = true;
//                    break;
//                }
//
//                fromRangeIndex = toRangeIndex;
//                continue;
//            }
//
//            if (DatesComparator.compare(rightDate(localRanges.get(toRangeIndex).getTo()), rightDate(newRange.getTo())) >= 0) {
//                break;
//            }
//        }
//
//        if (rangeAlreadyInCache) {
//            updateExistingRange(localRanges.get(toRangeIndex), newRange);
//            return;
//        }
//
//        TimeSeriesEntry[] mergedValues = mergeRanges(fromRangeIndex, toRangeIndex, localRanges, newRange);
//        addToCache(name, newRange.getFrom(), newRange.getTo(), fromRangeIndex, toRangeIndex, localRanges, cache, mergedValues);
//    }

//    static void addToCache(String timeseries, Date from, Date to, int fromRangeIndex, int toRangeIndex,
//                           List<TimeSeriesRangeResult> ranges, Map<String, List<TimeSeriesRangeResult>> cache,
//                           TimeSeriesEntry[] values) {
//        if (fromRangeIndex == -1) {
//            // didn't find a 'fromRange' => all ranges in cache start after 'from'
//
//            if (toRangeIndex == ranges.size()) {
//                // the requested range [from, to] contains all the ranges that are in cache
//
//                // e.g. if cache is : [[2,3], [4,5], [7, 10]]
//                // and the requested range is : [1, 15]
//                // after this action cache will be : [[1, 15]]
//
//                TimeSeriesRangeResult timeSeriesRangeResult = new TimeSeriesRangeResult();
//                timeSeriesRangeResult.setFrom(from);
//                timeSeriesRangeResult.setTo(to);
//                timeSeriesRangeResult.setEntries(values);
//
//                List<TimeSeriesRangeResult> result = new ArrayList<>();
//                result.add(timeSeriesRangeResult);
//                cache.put(timeseries, result);
//
//                return;
//            }
//
//            if (DatesComparator.compare(leftDate(ranges.get(toRangeIndex).getFrom()), rightDate(to)) > 0) {
//                // requested range ends before 'toRange' starts
//                // remove all ranges that come before 'toRange' from cache
//                // add the new range at the beginning of the list
//
//                // e.g. if cache is : [[2,3], [4,5], [7,10]]
//                // and the requested range is : [1,6]
//                // after this action cache will be : [[1,6], [7,10]]
//
//                ranges.subList(0, toRangeIndex).clear();
//                TimeSeriesRangeResult timeSeriesRangeResult = new TimeSeriesRangeResult();
//                timeSeriesRangeResult.setFrom(from);
//                timeSeriesRangeResult.setTo(to);
//                timeSeriesRangeResult.setEntries(values);
//
//                ranges.add(0, timeSeriesRangeResult);
//
//                return;
//            }
//
//            // the requested range ends inside 'toRange'
//            // merge the result from server into 'toRange'
//            // remove all ranges that come before 'toRange' from cache
//
//            // e.g. if cache is : [[2,3], [4,5], [7,10]]
//            // and the requested range is : [1,8]
//            // after this action cache will be : [[1,10]]
//
//            ranges.get(toRangeIndex).setFrom(from);
//            ranges.get(toRangeIndex).setEntries(values);
//            ranges.subList(0, toRangeIndex).clear();
//
//            return;
//        }
//
//        // found a 'fromRange'
//
//        if (toRangeIndex == ranges.size()) {
//            // didn't find a 'toRange' => all the ranges in cache end before 'to'
//
//            if (DatesComparator.compare(rightDate(ranges.get(fromRangeIndex).getTo()), leftDate(from)) < 0) {
//                // requested range starts after 'fromRange' ends,
//                // so it needs to be placed right after it
//                // remove all the ranges that come after 'fromRange' from cache
//                // add the merged values as a new range at the end of the list
//
//                // e.g. if cache is : [[2,3], [5,6], [7,10]]
//                // and the requested range is : [4,12]
//                // then 'fromRange' is : [2,3]
//                // after this action cache will be : [[2,3], [4,12]]
//
//
//                ranges.subList(fromRangeIndex + 1, ranges.size()).clear();
//                TimeSeriesRangeResult timeSeriesRangeResult = new TimeSeriesRangeResult();
//                timeSeriesRangeResult.setFrom(from);
//                timeSeriesRangeResult.setTo(to);
//                timeSeriesRangeResult.setEntries(values);
//
//                ranges.add(timeSeriesRangeResult);
//
//                return;
//            }
//
//            // the requested range starts inside 'fromRange'
//            // merge result into 'fromRange'
//            // remove all the ranges from cache that come after 'fromRange'
//
//            // e.g. if cache is : [[2,3], [4,6], [7,10]]
//            // and the requested range is : [5,12]
//            // then 'fromRange' is [4,6]
//            // after this action cache will be : [[2,3], [4,12]]
//
//            ranges.get(fromRangeIndex).setTo(to);
//            ranges.get(fromRangeIndex).setEntries(values);
//            ranges.subList(fromRangeIndex + 1, ranges.size()).clear();
//
//            return;
//        }
//
//        // found both 'fromRange' and 'toRange'
//        // the requested range is inside cache bounds
//
//        if (DatesComparator.compare(rightDate(ranges.get(fromRangeIndex).getTo()), leftDate(from)) < 0) {
//            // requested range starts after 'fromRange' ends
//
//            if (DatesComparator.compare(leftDate(ranges.get(toRangeIndex).getFrom()), rightDate(to)) > 0)
//            {
//                // requested range ends before 'toRange' starts
//
//                // remove all ranges in between 'fromRange' and 'toRange'
//                // place new range in between 'fromRange' and 'toRange'
//
//                // e.g. if cache is : [[2,3], [5,6], [7,8], [10,12]]
//                // and the requested range is : [4,9]
//                // then 'fromRange' is [2,3] and 'toRange' is [10,12]
//                // after this action cache will be : [[2,3], [4,9], [10,12]]
//
//                ranges.subList(fromRangeIndex + 1, toRangeIndex).clear();
//
//                TimeSeriesRangeResult timeSeriesRangeResult = new TimeSeriesRangeResult();
//                timeSeriesRangeResult.setFrom(from);
//                timeSeriesRangeResult.setTo(to);
//                timeSeriesRangeResult.setEntries(values);
//
//                ranges.add(fromRangeIndex + 1, timeSeriesRangeResult);
//
//                return;
//            }
//
//            // requested range ends inside 'toRange'
//
//            // merge the new range into 'toRange'
//            // remove all ranges in between 'fromRange' and 'toRange'
//
//            // e.g. if cache is : [[2,3], [5,6], [7,10]]
//            // and the requested range is : [4,9]
//            // then 'fromRange' is [2,3] and 'toRange' is [7,10]
//            // after this action cache will be : [[2,3], [4,10]]
//
//            ranges.subList(fromRangeIndex + 1, toRangeIndex).clear();
//            ranges.get(toRangeIndex).setFrom(from);
//            ranges.get(toRangeIndex).setEntries(values);
//
//            return;
//        }
//
//        // the requested range starts inside 'fromRange'
//
//        if (DatesComparator.compare(leftDate(ranges.get(toRangeIndex).getFrom()), rightDate(to)) > 0)
//        {
//            // requested range ends before 'toRange' starts
//
//            // remove all ranges in between 'fromRange' and 'toRange'
//            // merge new range into 'fromRange'
//
//            // e.g. if cache is : [[2,4], [5,6], [8,10]]
//            // and the requested range is : [3,7]
//            // then 'fromRange' is [2,4] and 'toRange' is [8,10]
//            // after this action cache will be : [[2,7], [8,10]]
//
//            ranges.get(fromRangeIndex).setTo(to);
//            ranges.get(fromRangeIndex).setEntries(values);
//            ranges.subList(fromRangeIndex + 1, toRangeIndex).clear();
//
//            return;
//        }
//
//        // the requested range starts inside 'fromRange'
//        // and ends inside 'toRange'
//
//        // merge all ranges in between 'fromRange' and 'toRange'
//        // into a single range [fromRange.From, toRange.To]
//
//        // e.g. if cache is : [[2,4], [5,6], [8,10]]
//        // and the requested range is : [3,9]
//        // then 'fromRange' is [2,4] and 'toRange' is [8,10]
//        // after this action cache will be : [[2,10]]
//
//        ranges.get(fromRangeIndex).setTo(ranges.get(toRangeIndex).getTo());
//        ranges.get(fromRangeIndex).setEntries(values);
//        ranges.subList(fromRangeIndex + 1, toRangeIndex + 1).clear();
//    }
//
//    private static TimeSeriesRangeResult parseTimeSeriesRangeResult(ObjectMapper mapper, ObjectNode jsonRange, String id, String databaseName) {
//        return mapper.convertValue(jsonRange, TimeSeriesRangeResult.class);
//    }
//
//    private static TimeSeriesEntry[] mergeRanges(int fromRangeIndex, int toRangeIndex, List<TimeSeriesRangeResult> localRanges, TimeSeriesRangeResult newRange) {
//        List<TimeSeriesEntry> mergedValues = new ArrayList<>();
//
//        if (fromRangeIndex != -1 && localRanges.get(fromRangeIndex).getTo().getTime() >= newRange.getFrom().getTime()) {
//            for (TimeSeriesEntry val : localRanges.get(fromRangeIndex).getEntries()) {
//                if (val.getTimestamp().getTime() >= newRange.getFrom().getTime()) {
//                    break;
//                }
//                mergedValues.add(val);
//            }
//        }
//
//        mergedValues.addAll(Arrays.asList(newRange.getEntries()));
//
//        if (toRangeIndex < localRanges.size()
//                && DatesComparator.compare(leftDate(localRanges.get(toRangeIndex).getFrom()), rightDate(newRange.getTo())) <= 0) {
//            for (TimeSeriesEntry val : localRanges.get(toRangeIndex).getEntries()) {
//                if (val.getTimestamp().getTime() <= newRange.getTo().getTime()) {
//                    continue;
//                }
//                mergedValues.add(val);
//            }
//        }
//
//        return mergedValues.toArray(new TimeSeriesEntry[0]);
//    }
//
//    private static void updateExistingRange(TimeSeriesRangeResult localRange, TimeSeriesRangeResult newRange) {
//        List<TimeSeriesEntry> newValues = new ArrayList<>();
//        int index;
//        for (index = 0; index < localRange.getEntries().length; index++) {
//            if (localRange.getEntries()[index].getTimestamp().getTime() >= newRange.getFrom().getTime()) {
//                break;
//            }
//
//            newValues.add(localRange.getEntries()[index]);
//        }
//
//        newValues.addAll(Arrays.asList(newRange.getEntries()));
//
//        for (int j = 0; j < localRange.getEntries().length; j++) {
//            if (localRange.getEntries()[j].getTimestamp().getTime() <= newRange.getTo().getTime()) {
//                continue;
//            }
//
//            newValues.add(localRange.getEntries()[j]);
//        }
//
//        localRange.setEntries(newValues.toArray(new TimeSeriesEntry[0]));
//    }
//

    public function hashCode(): int
    {
        return $this->hash;
    }

    /**
     * @throws ExceptionInterface
     */
    private function deserializeFromTransformer(string $entityType, string $id, array $document, bool $trackEntity)
    {
        return $this->entityToJson->convertToEntity($entityType, $id, $document, $trackEntity);
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

//    public boolean checkIfIdAlreadyIncluded(String[] ids, Map.Entry<String, Class< ? >>[] includes) {
//        return checkIfIdAlreadyIncluded(ids, Arrays.stream(includes).map(Map.Entry::getKey).collect(Collectors.toList()));
//    }
//
//    public boolean checkIfIdAlreadyIncluded(String[] ids, Collection<String> includes) {
//        for (String id : ids) {
//            if (_knownMissingIds.contains(id)) {
//                continue;
//            }
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
//        }
//
//        return true;
//    }

//    protected <T> void refreshInternal(T entity, RavenCommand<GetDocumentsResult> cmd, DocumentInfo documentInfo) {
//        ObjectNode document = (ObjectNode) cmd.getResult().getResults().get(0);
//        if (document == null) {
//            throw new IllegalStateException("Document '" + documentInfo.getId() + "' no longer exists and was probably deleted");
//        }
//
//        ObjectNode value = (ObjectNode) document.get(Constants.Documents.Metadata.KEY);
//        documentInfo.setMetadata(value);
//
//        if (documentInfo.getMetadata() != null) {
//            JsonNode changeVector = value.get(Constants.Documents.Metadata.CHANGE_VECTOR);
//            documentInfo.setChangeVector(changeVector.asText());
//        }
//
//        if (documentInfo.getEntity() != null && !noTracking) {
//            entityToJson.removeFromMissing(documentInfo.getEntity());
//        }
//
//        documentInfo.setEntity(entityToJson.convertToEntity(entity.getClass(), documentInfo.getId(), document, !noTracking));
//        documentInfo.setDocument(document);
//
//        try {
//            BeanUtils.copyProperties(entity, documentInfo.getEntity());
//        } catch (ReflectiveOperationException e) {
//            throw new RuntimeException("Unable to refresh entity: " + e.getMessage(), e);
//        }
//
//        DocumentInfo documentInfoById = documentsById.getValue(documentInfo.getId());
//
//        if (documentInfoById != null) {
//            documentInfoById.setEntity(entity);
//        }
//    }
//
//    @SuppressWarnings("unchecked")
//    protected static <T> T getOperationResult(Class<T> clazz, Object result) {
//        if (result == null) {
//            return Defaults.defaultValue(clazz);
//        }
//
//        if (clazz.isAssignableFrom(result.getClass())) {
//            return (T) result;
//        }
//
//        if (result instanceof Map) {
//            Map map = (Map) result;
//            if (map.isEmpty()) {
//                return null;
//            } else {
//                return (T) map.values().iterator().next();
//            }
//        }
//
//        throw new IllegalStateException("Unable to cast " + result.getClass().getSimpleName() + " to " + clazz.getSimpleName());
//    }

    protected function updateSessionAfterSaveChanges(BatchCommandResult $result): void
    {
        $returnedTransactionIndex = $result->getTransactionIndex();
        $this->documentStore->setLastTransactionIndex($this->getDatabaseName(), $returnedTransactionIndex);
        $this->sessionInfo->setLastClusterTransactionIndex($returnedTransactionIndex);
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

//    public void onAfterConversionToDocumentInvoke(String id, Object entity, Reference<ObjectNode> document) {
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
//    public void onBeforeConversionToEntityInvoke(String id, Class clazz, Reference<ObjectNode> document) {
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
//
//    protected Tuple<String, String> processQueryParameters(Class clazz, String indexName, String collectionName, DocumentConventions conventions) {
//        boolean isIndex = StringUtils.isNotBlank(indexName);
//        boolean isCollection = StringUtils.isNotEmpty(collectionName);
//
//        if (isIndex && isCollection) {
//            throw new IllegalStateException("Parameters indexName and collectionName are mutually exclusive. Please specify only one of them.");
//        }
//
//        if (!isIndex && !isCollection) {
//            collectionName = ObjectUtils.firstNonNull(
//                    conventions.getCollectionName(clazz),
//                    Constants.Documents.Metadata.ALL_DOCUMENTS_COLLECTION);
//        }
//
//        return Tuple.create(indexName, collectionName);
//    }
//
//    public static class SaveChangesData {
//        private final List<ICommandData> deferredCommands;
//        private final Map<IdTypeAndName, ICommandData> deferredCommandsMap;
//        private final List<ICommandData> sessionCommands = new ArrayList<>();
//        private final List<Object> entities = new ArrayList<>();
//        private final BatchOptions options;
//        private final ActionsToRunOnSuccess onSuccess;
//
//        public SaveChangesData(InMemoryDocumentSessionOperations session) {
//            deferredCommands = new ArrayList<>(session.deferredCommands);
//            deferredCommandsMap = new HashMap<>(session.deferredCommandsMap);
//            options = session._saveChangesOptions;
//            onSuccess = new ActionsToRunOnSuccess(session);
//        }
//
//        public ActionsToRunOnSuccess getOnSuccess() {
//            return onSuccess;
//        }
//
//        public List<ICommandData> getDeferredCommands() {
//            return deferredCommands;
//        }
//
//        public List<ICommandData> getSessionCommands() {
//            return sessionCommands;
//        }
//
//        public List<Object> getEntities() {
//            return entities;
//        }
//
//        public BatchOptions getOptions() {
//            return options;
//        }
//
//        public Map<IdTypeAndName, ICommandData> getDeferredCommandsMap() {
//            return deferredCommandsMap;
//        }
//
//        public static class ActionsToRunOnSuccess {
//
//            private final InMemoryDocumentSessionOperations _session;
//            private final List<String> _documentsByIdToRemove = new ArrayList<>();
//            private final List<Object> _documentsByEntityToRemove = new ArrayList<>();
//            private final List<Tuple<DocumentInfo, ObjectNode>> _documentInfosToUpdate = new ArrayList<>();
//
//            private boolean _clearDeletedEntities;
//
//            public ActionsToRunOnSuccess(InMemoryDocumentSessionOperations _session) {
//                this._session = _session;
//            }
//
//            public void removeDocumentById(String id) {
//                _documentsByIdToRemove.add(id);
//            }
//
//            public void removeDocumentByEntity(Object entity) {
//                _documentsByEntityToRemove.add(entity);
//            }
//
//            public void updateEntityDocumentInfo(DocumentInfo documentInfo, ObjectNode document) {
//                _documentInfosToUpdate.add(Tuple.create(documentInfo, document));
//            }
//
//            public void clearSessionStateAfterSuccessfulSaveChanges() {
//                for (String id : _documentsByIdToRemove) {
//                    _session.documentsById.remove(id);
//                }
//
//                for (Object entity : _documentsByEntityToRemove) {
//                    _session.documentsByEntity.remove(entity);
//                }
//
//                for (Tuple<DocumentInfo, ObjectNode> documentInfoObjectNodeTuple : _documentInfosToUpdate) {
//                    DocumentInfo info = documentInfoObjectNodeTuple.first;
//                    ObjectNode document = documentInfoObjectNodeTuple.second;
//                    info.setNewDocument(false);
//                    info.setDocument(document);
//                }
//
//                if (_clearDeletedEntities) {
//                    _session.deletedEntities.clear();
//                }
//
//                _session.deferredCommands.clear();
//                _session.deferredCommandsMap.clear();
//            }
//
//            public void clearDeletedEntities() {
//                _clearDeletedEntities = true;
//            }
//        }
//    }
//
//
//    public class ReplicationWaitOptsBuilder {
//
//        private BatchOptions getOptions() {
//            if (InMemoryDocumentSessionOperations.this._saveChangesOptions == null) {
//                InMemoryDocumentSessionOperations.this._saveChangesOptions = new BatchOptions();
//            }
//
//            if (InMemoryDocumentSessionOperations.this._saveChangesOptions.getReplicationOptions() == null) {
//                InMemoryDocumentSessionOperations.this._saveChangesOptions.setReplicationOptions(new ReplicationBatchOptions());
//            }
//
//            return InMemoryDocumentSessionOperations.this._saveChangesOptions;
//        }
//
//        public ReplicationWaitOptsBuilder withTimeout(Duration timeout) {
//            getOptions().getReplicationOptions().setWaitForReplicasTimeout(timeout);
//            return this;
//        }
//
//        public ReplicationWaitOptsBuilder throwOnTimeout(boolean shouldThrow) {
//            getOptions().getReplicationOptions().setThrowOnTimeoutInWaitForReplicas(shouldThrow);
//            return this;
//        }
//
//        public ReplicationWaitOptsBuilder numberOfReplicas(int replicas) {
//            getOptions().getReplicationOptions().setNumberOfReplicasToWaitFor(replicas);
//            return this;
//        }
//
//        public ReplicationWaitOptsBuilder majority(boolean waitForMajority) {
//            getOptions().getReplicationOptions().setMajority(waitForMajority);
//            return this;
//        }
//    }
//
//    public class IndexesWaitOptsBuilder {
//
//        private BatchOptions getOptions() {
//            if (InMemoryDocumentSessionOperations.this._saveChangesOptions == null) {
//                InMemoryDocumentSessionOperations.this._saveChangesOptions = new BatchOptions();
//            }
//
//            if (InMemoryDocumentSessionOperations.this._saveChangesOptions.getIndexOptions() == null) {
//                InMemoryDocumentSessionOperations.this._saveChangesOptions.setIndexOptions(new IndexBatchOptions());
//            }
//
//            return InMemoryDocumentSessionOperations.this._saveChangesOptions;
//        }
//
//        public IndexesWaitOptsBuilder withTimeout(Duration timeout) {
//            getOptions().getIndexOptions().setWaitForIndexesTimeout(timeout);
//            return this;
//        }
//
//        public IndexesWaitOptsBuilder throwOnTimeout(boolean shouldThrow) {
//            getOptions().getIndexOptions().setThrowOnTimeoutInWaitForIndexes(shouldThrow);
//            return this;
//        }
//
//        public IndexesWaitOptsBuilder waitForIndexes(String... indexes) {
//            getOptions().getIndexOptions().setWaitForSpecificIndexes(indexes);
//            return this;
//        }
//    }

    public function getTransactionMode(): TransactionMode
    {
        return $this->transactionMode;
    }

    public function setTransactionMode(TransactionMode $transactionMode): void
    {
        $this->transactionMode = $transactionMode;
    }

//    public static class DocumentsByEntityHolder implements Iterable<DocumentsByEntityHolder.DocumentsByEntityEnumeratorResult> {
//        private final Map<Object, DocumentInfo> _documentsByEntity = new IdentityLinkedHashMap<>();
//
//        private Map<Object, DocumentInfo> _onBeforeStoreDocumentsByEntity;
//
//        private boolean _prepareEntitiesPuts;
//
//        public int size() {
//            return _documentsByEntity.size() + (_onBeforeStoreDocumentsByEntity != null ? _onBeforeStoreDocumentsByEntity.size() : 0);
//        }
//
//        public void remove(Object entity) {
//            _documentsByEntity.remove(entity);
//            if (_onBeforeStoreDocumentsByEntity != null) {
//                _onBeforeStoreDocumentsByEntity.remove(entity);
//            }
//        }
//
//        public void evict(Object entity) {
//            if (_prepareEntitiesPuts) {
//                throw new IllegalStateException("Cannot Evict entity during OnBeforeStore");
//            }
//
//            _documentsByEntity.remove(entity);
//        }
//
//        public void put(Object entity, DocumentInfo documentInfo) {
//            if (!_prepareEntitiesPuts) {
//                _documentsByEntity.put(entity, documentInfo);
//                return;
//            }
//
//            createOnBeforeStoreDocumentsByEntityIfNeeded();
//            _onBeforeStoreDocumentsByEntity.put(entity, documentInfo);
//        }
//
//        private void createOnBeforeStoreDocumentsByEntityIfNeeded() {
//            if (_onBeforeStoreDocumentsByEntity != null) {
//                return ;
//            }
//
//            _onBeforeStoreDocumentsByEntity = new IdentityLinkedHashMap<>();
//        }
//
//        public void clear() {
//            _documentsByEntity.clear();
//            if (_onBeforeStoreDocumentsByEntity != null) {
//                _onBeforeStoreDocumentsByEntity.clear();
//            }
//        }
//
//        public DocumentInfo get(Object entity) {
//            DocumentInfo documentInfo = _documentsByEntity.get(entity);
//            if (documentInfo != null) {
//                return documentInfo;
//            }
//
//            if (_onBeforeStoreDocumentsByEntity != null) {
//                return _onBeforeStoreDocumentsByEntity.get(entity);
//            }
//
//            return null;
//        }
//
//        @Override
//        public Iterator<DocumentsByEntityEnumeratorResult> iterator() {
//            Iterator<DocumentsByEntityEnumeratorResult> firstIterator
//                    = Iterators.transform(_documentsByEntity.entrySet().iterator(),
//                        x -> new DocumentsByEntityEnumeratorResult(x.getKey(), x.getValue(), true));
//
//            if (_onBeforeStoreDocumentsByEntity == null) {
//                return firstIterator;
//            }
//
//            Iterator<DocumentsByEntityEnumeratorResult> secondIterator
//                    = Iterators.transform(_onBeforeStoreDocumentsByEntity.entrySet().iterator(),
//                        x -> new DocumentsByEntityEnumeratorResult(x.getKey(), x.getValue(), false));
//
//            return Iterators.concat(firstIterator, secondIterator);
//        }
//
//        @Override
//        public Spliterator<DocumentsByEntityEnumeratorResult> spliterator() {
//            return Spliterators.spliterator(iterator(), size(), Spliterator.ORDERED);
//        }
//
//        public CleanCloseable prepareEntitiesPuts() {
//            _prepareEntitiesPuts = true;
//
//            return () -> _prepareEntitiesPuts = false;
//        }
//
//        public static class DocumentsByEntityEnumeratorResult {
//            private Object key;
//            private DocumentInfo value;
//            private boolean executeOnBeforeStore;
//
//            public DocumentsByEntityEnumeratorResult(Object key, DocumentInfo value, boolean executeOnBeforeStore) {
//                this.key = key;
//                this.value = value;
//                this.executeOnBeforeStore = executeOnBeforeStore;
//            }
//
//            public Object getKey() {
//                return key;
//            }
//
//            public DocumentInfo getValue() {
//                return value;
//            }
//
//            public boolean isExecuteOnBeforeStore() {
//                return executeOnBeforeStore;
//            }
//
//        }
//
//    }
//
//    public static class DeletedEntitiesHolder implements Iterable<DeletedEntitiesHolder.DeletedEntitiesEnumeratorResult> {
//        private final Set<Object> _deletedEntities = new IdentityHashSet<>();
//
//        private Set<Object> _onBeforeDeletedEntities;
//
//        private boolean _prepareEntitiesDeletes;
//
//        public boolean isEmpty() {
//            return size() == 0;
//        }
//
//        public int size() {
//            return _deletedEntities.size() + (_onBeforeDeletedEntities != null ? _onBeforeDeletedEntities.size() : 0);
//        }
//
//        public void add(Object entity) {
//            if (_prepareEntitiesDeletes) {
//                if (_onBeforeDeletedEntities == null) {
//                    _onBeforeDeletedEntities = new IdentityHashSet<>();
//                }
//
//                _onBeforeDeletedEntities.add(entity);
//                return;
//            }
//
//            _deletedEntities.add(entity);
//        }
//
//        public void remove(Object entity) {
//            _deletedEntities.remove(entity);
//            if (_onBeforeDeletedEntities != null) {
//                _onBeforeDeletedEntities.remove(entity);
//            }
//        }
//
//        public void evict(Object entity) {
//            if (_prepareEntitiesDeletes) {
//                throw new IllegalStateException("Cannot Evict entity during OnBeforeDelete");
//            }
//
//            _deletedEntities.remove(entity);
//        }
//
//        public boolean contains(Object entity) {
//            if (_deletedEntities.contains(entity)) {
//                return true;
//            }
//
//            if (_onBeforeDeletedEntities == null) {
//                return false;
//            }
//
//            return _onBeforeDeletedEntities.contains(entity);
//        }
//
//        public void clear() {
//            _deletedEntities.clear();
//            if (_onBeforeDeletedEntities != null) {
//                _onBeforeDeletedEntities.clear();
//            }
//        }
//
//        @Override
//        public Iterator<DeletedEntitiesEnumeratorResult> iterator() {
//            Iterator<Object> deletedIterator = _deletedEntities.iterator();
//            Iterator<DeletedEntitiesEnumeratorResult> deletedTransformedIterator
//                    = Iterators.transform(deletedIterator, x -> new DeletedEntitiesEnumeratorResult(x, true));
//
//            if (_onBeforeDeletedEntities == null) {
//                return deletedTransformedIterator;
//            }
//
//            Iterator<DeletedEntitiesEnumeratorResult> onBeforeDeletedIterator
//                    = Iterators.transform(_deletedEntities.iterator(), x -> new DeletedEntitiesEnumeratorResult(x, false));
//
//            return Iterators.concat(deletedTransformedIterator, onBeforeDeletedIterator);
//        }
//
//        @Override
//        public Spliterator<DeletedEntitiesEnumeratorResult> spliterator() {
//            return Spliterators.spliterator(iterator(), size(), Spliterator.ORDERED);
//        }
//
//        public CleanCloseable prepareEntitiesDeletes() {
//            _prepareEntitiesDeletes = true;
//
//            return () -> _prepareEntitiesDeletes = false;
//        }
//
//        public static class DeletedEntitiesEnumeratorResult {
//            private Object entity;
//            private boolean executeOnBeforeDelete;
//
//            public DeletedEntitiesEnumeratorResult(Object entity, boolean executeOnBeforeDelete) {
//                this.entity = entity;
//                this.executeOnBeforeDelete = executeOnBeforeDelete;
//            }
//
//            public Object getEntity() {
//                return entity;
//            }
//
//            public boolean isExecuteOnBeforeDelete() {
//                return executeOnBeforeDelete;
//            }
//
//        }
//    }

    private function removeIdFromKnownMissingIds(string $id): void
    {
        if (($key = array_search($id, $this->knownMissingIds)) !== false) {
            unset($this->knownMissingIds[$key]);
        }
    }
}
