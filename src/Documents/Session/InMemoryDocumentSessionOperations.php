<?php

namespace RavenDB\Documents\Session;

use Closure;
use DateTime;
use DateTimeInterface;
use Ds\Map as DSMap;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Constants\Headers;
use RavenDB\Documents\Operations\OperationExecutor;
use RavenDB\Documents\Commands\Batches\BatchOptions;
use RavenDB\Documents\Commands\Batches\IndexBatchOptions;
use RavenDB\Documents\Operations\SessionOperationExecutor;
use RavenDB\Documents\Commands\Batches\BatchPatchCommandData;
use RavenDB\Documents\Commands\Batches\CommandDataInterface;
use RavenDB\Documents\Commands\Batches\CommandType;
use RavenDB\Documents\Commands\Batches\DeleteCommandData;
use RavenDB\Documents\Commands\Batches\ForceRevisionCommandData;
use RavenDB\Documents\Commands\Batches\IdAndChangeVector;
use RavenDB\Documents\Commands\Batches\PutCommandDataWithJson;
use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreBase;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Identity\GenerateEntityIdOnTheClient;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeResult;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeResultList;
use RavenDB\Documents\Session\Operations\Lazy\LazyOperationList;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntry;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntryArray;
use RavenDB\Exceptions\Documents\Session\NonUniqueObjectException;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\NotImplementedException;
use RavenDB\Extensions\EntityMapper;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Extensions\StringExtensions;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\RequestExecutor;
use RavenDB\Http\ServerNode;
use RavenDB\Json\BatchCommandResult;
use RavenDB\Json\JsonOperation;
use RavenDB\Json\MetadataAsDictionary;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Primitives\ClosureArray;
use RavenDB\Primitives\DatesComparator;
use RavenDB\Primitives\EventHelper;
use RavenDB\Primitives\NetISO8601Utils;
use RavenDB\Type\DeferredCommandsMap;
use RavenDB\Type\ExtendedArrayObject;
use RavenDB\Type\ObjectArray;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringList;
use RavenDB\Utils\AtomicInteger;
use RavenDB\Utils\StringUtils;
use ReflectionClass;
use ReflectionObject;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

abstract class InMemoryDocumentSessionOperations implements CleanCloseable
{
    protected RequestExecutor $requestExecutor;

    private ?OperationExecutor $operationExecutor = null;

    protected ?LazyOperationList $pendingLazyOperations = null;
    protected ?DSMap $onEvaluateLazy = null;

    private static ?AtomicInteger $instancesCounter = null;

    private int $hash = 0;
    protected bool $generateDocumentKeysOnStore = true;
    protected SessionInfo $sessionInfo;
    public ?BatchOptions $saveChangesOptions = null;

    public bool $disableAtomicDocumentWritesInClusterWideTransaction;

    protected TransactionMode $transactionMode;

    protected bool $isDisposed = false;

    protected ?EntityMapper $mapper = null;

    public function getMapper(): EntityMapper
    {
        return $this->mapper;
    }

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

    public function addAfterConversionToDocumentListener(Closure $handler)
    {
        $this->onAfterConversionToDocument->append($handler);
    }

    public function removeAfterConversionToDocumentListener(Closure $handler)
    {
        $this->onAfterConversionToDocument->removeValue($handler);
    }

    public function addBeforeConversionToEntityListener(Closure $handler)
    {
        $this->onBeforeConversionToEntity->append($handler);
    }

    public function removeBeforeConversionToEntityListener(Closure $handler)
    {
        $this->onBeforeConversionToEntity->removeValue($handler);
    }

    public function addAfterConversionToEntityListener(Closure $handler)
    {
        $this->onAfterConversionToEntity->append($handler);
    }

    public function removeAfterConversionToEntityListener(Closure $handler)
    {
        $this->onAfterConversionToEntity->removeValue($handler);
    }

    public function addOnSessionClosingListener(Closure $handler)
    {
        $this->onSessionClosing->append($handler);
    }

    public function removeOnSessionClosingListener(Closure $handler)
    {
        $this->onSessionClosing->removeValue($handler);
    }

    // @todo: This should be set of strings / not array !!! - fix this in future (now it's working like this)

    //Entities whose id we already know do not exist, because they are a missing include, or a missing load, etc.
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
    public ?DocumentInfoArray $includedDocumentsById = null;

    /**
     * Translate between an CV and its associated entity
     */
    public ?DocumentInfoArray $includeRevisionsByChangeVector = null;

    /**
     * Translate between an ID and its associated entity
     *
     * DocumentInfoDatesArray = Map<String, Map<Date, DocumentInfo>>
     *
     */
    public ?DocumentInfoDatesArray $includeRevisionsIdByDateTimeBefore = null;

    /**
     * hold the data required to manage the data for RavenDB's Unit of Work
     */
    public DocumentsByEntityHolder $documentsByEntity;

    /**
     * The entities waiting to be deleted
     */
    public DeletedEntitiesHolder $deletedEntities;

    /**
     * @return array map which holds the data required to manage Counters tracking for RavenDB's Unit of Work
     */
    public function & getCountersByDocId(): array // Map<String, Tuple<Boolean, Map<String, Long>>>
    {
        if ($this->countersByDocId == null) {
            $this->countersByDocId = []; //new TreeMap<>(String::compareToIgnoreCase);
        }
        return $this->countersByDocId;
    }

    // @todo: Change from array to adequate format
//    private Map<String, Tuple<Boolean, Map<String, Long>>> _countersByDocId;
    private array $countersByDocId = [];

    // @todo: Change from array to adequate format
//    private Map<String, Map<String, List<TimeSeriesRangeResult>>> _timeSeriesByDocId;
    private array $timeSeriesByDocId = [];

    // @todo: Change return type from array to adequate format
    public function & getTimeSeriesByDocId(): array // Map<String, Map<String, List<TimeSeriesRangeResult>>>
    {
        if ($this->timeSeriesByDocId == null) {
            $this->timeSeriesByDocId = [];
        }

        return $this->timeSeriesByDocId;
    }

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

    public function getOperations(): OperationExecutor
    {
        if ($this->operationExecutor == null) {
            $this->operationExecutor = new SessionOperationExecutor($this);
        }

        return $this->operationExecutor;
    }

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

    /**
     * Gets the conventions used by this session
     * This instance is shared among all sessions, changes to the DocumentConventions should be done
     * via the DocumentStoreInterface instance, not on a single session.
     *
     * @return DocumentConventions document conventions
     */
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
    public ?DeferredCommandsMap $deferredCommandsMap = null;

    public bool $noTracking;

    public ?ForceRevisionStrategyArray $idsForCreatingForcedRevisions = null;

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

        $this->pendingLazyOperations = new LazyOperationList();
        $this->onEvaluateLazy = new DSMap();

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
        $this->deferredCommandsMap = new DeferredCommandsMap();

        $this->entityToJson = new EntityToJson($this);

        $this->mapper = JsonExtensions::getDefaultMapper();

        $this->idsForCreatingForcedRevisions = new ForceRevisionStrategyArray();
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
    public function & getMetadataFor($instance): MetadataDictionaryInterface
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
     * Gets all counter names for the specified entity.
     * @template T Class of instance
     *
     * @param T $instance The instance
     * @return null|StringList List of all counter names
     */
    public function getCountersFor(mixed $instance): ?StringList
    {
        if ($instance == null) {
            throw new IllegalArgumentException("Instance cannot be null");
        }

        $documentInfo = $this->getDocumentInfo($instance);

        if (!array_key_exists(DocumentsMetadata::COUNTERS, $documentInfo->getMetadata())) {
            return null;
        }

        $countersArray = $documentInfo->getMetadata()[DocumentsMetadata::COUNTERS];

        return StringList::fromArray($countersArray);
    }

    /**
     * Gets all time series names for the specified instance.
     * Throws an exception if the instance is not tracked by the session.
     * @param ?object $instance Entity
     * @return array time series names
     *
     * @throws NonUniqueObjectException
     */
    public function getTimeSeriesFor(?object $instance): array
    {
        if ($instance == null) {
            throw new IllegalArgumentException("Instance cannot be null");
        }

        $documentInfo = $this->getDocumentInfo($instance);

        $metadata = $documentInfo->getMetadata();
        $array = array_key_exists(DocumentsMetadata::TIME_SERIES, $metadata) ? $metadata[DocumentsMetadata::TIME_SERIES] : null;

        if ($array == null) {
            return [];
        }

        $tsList = [];

        foreach ($array as $jsonNode) {
            $tsList[] = strval($jsonNode);
        }

        return $tsList;
    }

    /**
     * Gets the Change Vector for the specified instance.
     * Throws an exception if the instance is not tracked by the session.
     *
     * @param ?object $instance Instance to get change vector from
     * @return ?string change vector
     *
     * @throws NonUniqueObjectException
     */
    public function getChangeVectorFor(?object $instance): ?string
    {
        if ($instance == null) {
            throw new IllegalArgumentException("instance cannot be null");
        }

        $documentInfo = $this->getDocumentInfo($instance);
        $metadata = $changeVector = $documentInfo->getMetadata();
        if (!array_key_exists(DocumentsMetadata::CHANGE_VECTOR, $metadata)) {
            return null;
        };
        return $metadata[DocumentsMetadata::CHANGE_VECTOR];
    }

    /**
     * @template T
     * @param ?T $instance
     * @return DateTimeInterface|null
     *
     * @throws NonUniqueObjectException
     */
    public function getLastModifiedFor($instance): ?DateTimeInterface
    {
        if ($instance == null) {
            throw new IllegalArgumentException("Instance cannot be null");
        }

        $documentInfo = $this->getDocumentInfo($instance);
        $lastModified = $documentInfo->getMetadata()[DocumentsMetadata::LAST_MODIFIED];
        if (!empty($lastModified)) {
            return $this->getConventions()->getEntityMapper()->denormalize($lastModified, DateTimeInterface::class);
        }
        return null;
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

    /**
     * Returns whether a document with the specified id is loaded in the
     * current session
     *
     * @param string $id Document id to check
     * @return bool true is document is loaded
     */
    public function isLoaded(string $id): bool
    {
        return $this->isLoadedOrDeleted($id);
    }

    public function isLoadedOrDeleted(string $id): bool
    {
        $documentInfo = $this->documentsById->getValue($id);
        return ($documentInfo != null && ($documentInfo->getDocument() != null || $documentInfo->getEntity() != null)) || $this->isDeleted($id) || $this->includedDocumentsById->offsetExists($id);
    }

    /**
     * Returns whether a document with the specified id is deleted
     * or known to be missing
     *
     * @param string $id Document id to check
     * @return bool true is document is deleted
     */
    public function isDeleted(string $id): bool
    {
        return in_array($id, $this->knownMissingIds);
    }

    /**
     * Gets the document id.
     *
     * @param ?object $instance instance to get document id from
     * @return ?string document id
     */
    public function getDocumentId(?object $instance): ?string
    {
        if ($instance == null) {
            return null;
        }
        $value = $this->documentsByEntity->get($instance);
        return $value != null ? $value->getId() : null;
    }

    public function incrementRequestCount(): void
    {
        $this->numberOfRequests += 1;
        if ($this->numberOfRequests > $this->maxNumberOfRequestsPerSession) {
            throw new IllegalStateException(sprintf(
                "The maximum number of requests (%d) allowed for this session has been reached. Raven limits " .
                "the number of remote calls that a session is allowed to make as an early warning system." .
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
     * @param string $className
     * @param string|DocumentInfo $idOrDocumentInfo
     * @param array|null $document
     * @param array|null $metadata
     * @param bool $noTracking
     *
     * @return object|null
     *
     * @throws ExceptionInterface
     */
    public function trackEntity(
        ?string $className,
                $idOrDocumentInfo,
        ?array  $document = null,
        ?array  $metadata = null,
        bool    $noTracking = false
    ): ?object
    {

        if ($idOrDocumentInfo instanceof DocumentInfo) {
            return $this->trackEntityInternal(
                $className,
                $idOrDocumentInfo->getId(),
                $idOrDocumentInfo->getDocument(),
                $idOrDocumentInfo->getMetadata(),
                $this->noTracking
            );
        }

        return $this->trackEntityInternal($className, $idOrDocumentInfo, $document, $metadata, $noTracking);
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
        ?string $entityType,
        ?string $id,
        array   $document,
        array   $metadata,
        bool    $noTracking
    ): ?object
    {
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

        $docInfo = $this->includedDocumentsById->getValue($id);
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
            $this->documentsByEntity->put($entity, $newDocumentInfo);
        }

        return $entity;
    }

    /**
     * Gets the default value of the specified type.
     *
     * @param string $className Class
     * @return mixed Default value to given class
     */
    public static function getDefaultValue(string $className): mixed
    {
        return null; // Defaults::defaultValue($className);
    }

    /**
     * Marks the specified entity for deletion. The entity will be deleted when IDocumentSession.saveChanges is called.
     *
     * Usage
     *  - delete(?object $entity): void;
     *  - delete(?string $id): void;
     *  - delete(?string $id, ?string $expectedChangeVector): void;
     *
     * @param string|object|null $entity
     * @param string|null $expectedChangeVector
     *
     * @throws IllegalArgumentException
     * @throws IllegalStateException
     */
    public function delete(string|object|null $entity, ?string $expectedChangeVector = null): void
    {
        if (is_object($entity)) {
            $this->deleteEntity($entity);
            return;
        }

        $this->deleteById($entity, $expectedChangeVector);
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
            throw new IllegalStateException(StringExtensions::stringFromEntity($entity) ?? 'Object' . ' is not associated with the session, cannot delete unknown entity instance');
        }

        $this->deletedEntities->add($entity);
        $this->includedDocumentsById->remove($value->getId());
        if ($this->countersByDocId !== null) {
            if (array_key_exists($value->getId(), $this->countersByDocId)) {
                unset($this->countersByDocId[$id]);
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
            if (array_key_exists($id, $this->countersByDocId)) {
                unset($this->countersByDocId[$id]);
            }
        }

        $this->defer(new DeleteCommandData(
            $id,
            $expectedChangeVector ?? $changeVector,
            $expectedChangeVector ?? $documentInfo?->getChangeVector()
        ));
    }

    /**
     * Store entities inside the session object.
     *
     * Usage:
     *  - public function store(?object $entity): void;
     *  - public function store(?object $entity, ?string $id): void;
     *  - public function store(?object $entity, ?string $id, ?string $changeVector): void;
     *
     * @throws IllegalStateException|InvalidArgumentException|NonUniqueObjectException|ExceptionInterface
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
        ?object              $entity,
        ?string              $id,
        ?string              $changeVector,
        ConcurrencyCheckMode $forceConcurrencyCheck
    ): void
    {
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
            if ($id != null && strcasecmp($value->getId(), $id)) {
                throw new IllegalStateException("Cannot store the same entity (id: " . $value->getId() . ") with different id (" . $id . ")");
            }
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


        if ($this->deferredCommandsMap->hasKeyWith($id, CommandType::clientAnyCommand(), null)) {
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
            $metadata[DocumentsMetadata::COLLECTION] = $mapper->normalize($collectionName, 'json');
        }

        $phpType = $this->requestExecutor->getConventions()->getPhpClassName($entity);
        if ($phpType != null) {
            $metadata[DocumentsMetadata::RAVEN_PHP_TYPE] = strval($phpType);
        }

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
        ?string              $id,
        ?object              $entity,
        ?string              $changeVector,
        array                $metadata,
        ConcurrencyCheckMode $forceConcurrencyCheck
    ): void
    {
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

        $this->prepareForEntitiesDeletion($result);
        $this->prepareForEntitiesPuts($result);
        $this->prepareForCreatingRevisionsFromIds($result);
        $this->prepareCompareExchangeEntities($result);

        if (count($this->deferredCommands) > $deferredCommandsCount) {
            // this allow OnBeforeStore to call Defer during the call to include
            // additional values during the same SaveChanges call

            for ($i = $deferredCommandsCount; $i < count($this->deferredCommands); $i++) {
                $result->addDeferredCommand($this->deferredCommands[$i]);
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

    public function validateClusterTransaction(SaveChangesData $result): void
    {
        if (!$this->transactionMode->isClusterWide()) {
            return;
        }

        if ($this->isUseOptimisticConcurrency()) {
            throw new IllegalStateException("useOptimisticConcurrency is not supported with TransactionMode set to " . TransactionMode::CLUSTER_WIDE);
        }

        /** @var  CommandDataInterface $commandData */
        foreach ($result->getSessionCommands() as $commandData) {
            switch ($commandData->getType()->getValue()) {
                case CommandType::PUT :
                case CommandType::DELETE :
                    if ($commandData->getChangeVector() != null) {
                        throw new IllegalStateException("Optimistic concurrency for " . $commandData->getId() . " is not supported when using a cluster transaction");
                    }
                    break;
                case CommandType::COMPARE_EXCHANGE_DELETE :
                case CommandType::COMPARE_EXCHANGE_PUT :
                    break;
                default:
                    throw new IllegalStateException("The command '" . $commandData->getType()->getValue() . "' is not supported in a cluster session.");
            }
        }
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
    public static function updateMetadataModifications(?MetadataDictionaryInterface $metadataDictionary, array & $metadata): bool
    {
        $dirty = false;
        $mapper = JsonExtensions::getDefaultMapper();
        if ($metadataDictionary != null) {
            if ($metadataDictionary->isDirty()) {
                $dirty = true;
            }

            foreach ($metadataDictionary->toSimpleArray() as $prop => $propValue) {
                if (($propValue == null) || (($propValue instanceof MetadataAsDictionary) && ($propValue->isDirty()))) {
                    $dirty = true;
                }

                $metadata[$prop] = $mapper->normalize($propValue);
            }

            if (count($metadata) != $metadataDictionary->count()) {
                // looks like some props were removed
                $toRemove = [];
                foreach ($metadata as $fieldName => $value) {
                    if (!$metadataDictionary->containsKey($fieldName)) {
                        $toRemove[] = $fieldName;
                    }
                }

                foreach ($toRemove as $s) {
                    unset($metadata[$s]);
                }
            }
        }

        return $dirty;
    }


    private function prepareForCreatingRevisionsFromIds(SaveChangesData $result): void
    {
        // Note: here there is no point checking 'Before' or 'After' because if there were changes then forced revision is done from the PUT command....

        foreach (array_keys($this->idsForCreatingForcedRevisions->getArrayCopy()) as $idEntry) {
            $result->addSessionCommand(new ForceRevisionCommandData($idEntry));
        }

        $this->idsForCreatingForcedRevisions->clear();
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

                if ($changes !== null) {
                    $docChanges = [];

                    $change = new DocumentsChanges();
                    $change->setFieldNewValue([]);
                    $change->setFieldOldValue([]);
                    $change->setChange(ChangeType::documentDeleted());

                    $docChanges[] = $change;
                    $changes[$documentInfo->getId()] = $docChanges;
                } else {
                    $commandIndex = $this->deferredCommandsMap->getIndexFor($documentInfo->getId(), CommandType::clientAnyCommand(), null);
                    if ($commandIndex != null) {
                        $this->throwInvalidDeletedDocumentWithDeferredCommand($this->deferredCommandsMap->get($commandIndex));
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

    private function prepareForEntitiesPuts(?SaveChangesData $result): void
    {
        $putsContext = $this->documentsByEntity->prepareEntitiesPuts();

        try {
            $shouldIgnoreEntityChanges = $this->getConventions()->getShouldIgnoreEntityChanges();

            /** @var DocumentsByEntityEnumeratorResult $entity */
            foreach ($this->documentsByEntity->getDocumentsByEntityEnumeratorResults() as $entity) {

                if ($entity->getValue()->isIgnoreChanges()) {
                    continue;
                }

                if ($shouldIgnoreEntityChanges != null) {
                    if ($shouldIgnoreEntityChanges(
                        $this,
                        $entity->getValue()->getEntity(),
                        $entity->getValue()->getId())) {
                        continue;
                    }
                }

                if ($this->isDeleted($entity->getValue()->getId())) {
                    continue;
                }

                $dirtyMetadata = $this->updateMetadataModifications($entity->getValue()->getMetadataInstance(), $entity->getValue()->getMetadata());

                $document = $this->entityToJson->convertEntityToJson($entity->getKey(), $entity->getValue());

                if (!$this->isEntityChanged($document, $entity->getValue()) && !$dirtyMetadata) {
                    continue;
                }

                $commandIndex = $this->deferredCommandsMap->getIndexFor($entity->getValue()->getId(), CommandType::clientModifyDocumentCommand(), null);
                if ($commandIndex !== null) {
                    $this->throwInvalidModifiedDocumentWithDeferredCommand($this->deferredCommandsMap->get($commandIndex));
                }

                $onBeforeStore = $this->onBeforeStore;

                if (count($onBeforeStore) && $entity->isExecuteOnBeforeStore()) {
                    $beforeStoreEventArgs = new BeforeStoreEventArgs($this, $entity->getValue()->getId(), $entity->getKey());

                    EventHelper::invoke($onBeforeStore, $this, $beforeStoreEventArgs);


                    if ($beforeStoreEventArgs->isMetadataAccessed()) {
                        $this->updateMetadataModifications($entity->getValue()->getMetadataInstance(), $entity->getValue()->getMetadata());
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
                    if (!$entity->getValue()->getConcurrencyCheckMode()->isDisabled()) {
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
                    if ($this->idsForCreatingForcedRevisions->offsetExists($entity->getValue()->getId())) {
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
     * @throws IllegalArgumentException
     * @deprecated
     *
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

    /**
     * Returns all changes for the specified entity. Including name of the field/property that changed, its old and new value and change type.
     * @param object $entity
     * @return DocumentsChangesArray list of changes
     */
    public function whatChangedFor(object $entity): DocumentsChangesArray
    {
        $documentInfo = $this->documentsByEntity->get($entity);
        if ($documentInfo == null) {
            return new DocumentsChangesArray();
        }

        if ($this->deletedEntities->contains($entity)) {
            $change = new DocumentsChanges();
            $change->setFieldNewValue("");
            $change->setFieldOldValue("");
            $change->setChange(ChangeType::documentDeleted());

            return DocumentsChangesArray::fromArray([$change]);
        }

        $this->updateMetadataModifications($documentInfo->getMetadataInstance(), $documentInfo->getMetadata());
        $document = $this->entityToJson->convertEntityToJson($documentInfo->getEntity(), $documentInfo);

        $changes = [];

        $changes = $this->getEntityChanges($document, $documentInfo);
        if (empty($changes)) {
            return new DocumentsChangesArray();
        }

        return $changes[$documentInfo->getId()];
    }

    public function getTrackedEntities(): EntityInfoMap
    {
        $tracked = $this->documentsById->getTrackedEntities($this);

        foreach ($this->knownMissingIds as $id) {
            if ($tracked->offsetExists($id)) {
                continue;
            }

            $entityInfo = new EntityInfo();
            $entityInfo->setId($id);
            $entityInfo->setDeleted(true);

            $tracked[$id] = $entityInfo;
        }

        return $tracked;
    }

    /**
     * Gets a value indicating whether any of the entities tracked by the session has changes.
     *
     * @return bool true if session has changes
     */
    public function hasChanges(): bool
    {
        /** @var DocumentsByEntityEnumeratorResult $entity */
        foreach ($this->documentsByEntity->getDocumentsByEntityEnumeratorResults() as $entity) {
            $document = $this->entityToJson->convertEntityToJson($entity->getKey(), $entity->getValue());

            if ($this->isEntityChanged($document, $entity->getValue())) {
                return true;
            }
        }

        return !$this->deletedEntities->isEmpty() || !empty($this->deferredCommands);
    }

    /**
     * Determines whether the specified entity has changed.
     *
     * @param object $entity Entity to check
     * @return bool true if entity has changed
     */
    public function hasChanged(?object $entity): bool
    {
        $documentInfo = $this->documentsByEntity->get($entity);

        if ($documentInfo == null) {
            return false;
        }

        $document = $this->entityToJson->convertEntityToJson($entity, $documentInfo);
        return $this->isEntityChanged($document, $documentInfo);
    }

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

//    public function waitForIndexesAfterSaveChanges(): void
//    {
//        $waitForIndexesAfterSaveChanges(options -> {
//        });
//    }

    /**
     * @param ?Closure $options
     */
    public function waitForIndexesAfterSaveChanges(?Closure $options = null): void
    {
        $builder = new IndexesWaitOptsBuilder($this);
        if ($options) {
            $options($builder);
        }

        $builderOptions = $builder->getOptions();
        $indexOptions = $builderOptions->getIndexOptions();

        if ($indexOptions == null) {
            $builderOptions->setIndexOptions($indexOptions = new IndexBatchOptions());
        }

        if ($indexOptions->getWaitForIndexesTimeout() == null) {
            $indexOptions->setWaitForIndexesTimeout($this->getConventions()->getWaitForIndexesAfterSaveChangesTimeout());
        }

        $indexOptions->setWaitForIndexes(true);
    }

    /**
     * @throws ExceptionInterface
     * @throws IllegalArgumentException
     */
    private function getAllEntitiesChanges(): array
    {
        /** @var array<string, DocumentsChangesArray> $changes */
        $changes = array();

        /**
         * @var string $id
         * @var DocumentInfo $documentInfo
         */
        foreach ($this->documentsById as $id => $documentInfo) {
            $this->updateMetadataModifications($documentInfo->getMetadataInstance(), $documentInfo->getMetadata());
            $newObj = $this->entityToJson->convertEntityToJson($documentInfo->getEntity(), $documentInfo);
            $entityChanges = $this->getEntityChanges($newObj, $documentInfo);
            if (count($entityChanges)) {
                $changes[$documentInfo->getId()] = $entityChanges;
            }
        }

        return $changes;
    }

    /**
     * Mark the entity as one that should be ignored for change tracking purposes,
     * it still takes part in the session, but is ignored for SaveChanges.
     *
     * @param object $entity Entity for which changed should be ignored
     * @throws NonUniqueObjectException
     */
    public function ignoreChangesFor(object $entity): void
    {
        $this->getDocumentInfo($entity)->setIgnoreChanges(true);
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
        $this->clearClusterSession();
//        $this->pendingLazyOperations->clear();
        $this->entityToJson->clear();
    }

    /**
     * Defer commands to be executed on saveChanges()
     *
     * Usage:
     *   - defer(CommandDataInterface $command): void
     *   - defer(CommandDataInterface $command, array $commands): void
     *   - defer(array $commands): void
     *   - defer(CommandDataInterface ...$commands): void
     *
     * Example:
     *   - defer($cmd);
     *   - defer($cmd1, $cmd2, $cmd3, $cmd4 ...)
     *   - defer([$cmd1, $cmd2, $cmd4, $cmd4, ...])
     *   - defer($cmd1, [$cmd2, $cmd3])
     *
     * @param CommandDataInterface|array $commands More commands to defer
     */
    public function defer(...$commands): void
    {
        if (!count($commands)) {
            throw new InvalidArgumentException('You must call defer with command in parameter.');
        }

        foreach ($commands as $command) {
            if (is_array($command)) {
                $this->deferCommands($command);
                continue;
            }
            if ($command instanceof CommandDataInterface) {
                $this->deferCommand($command);
                continue;
            }

            throw new InvalidArgumentException('You called defer with invalid parameters');
        }
    }

    /**
     * Defer commands to be executed on saveChanges()
     *
     * @param CommandDataInterface $command Command to defer
     * @param ?array $commands More commands to defer
     */
    private function deferCommand(CommandDataInterface $command, array $commands = []): void
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
    private function deferCommands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->deferredCommands[] = $command;
        }

        foreach ($commands as $command) {
            $this->deferInternal($command);
        }
    }

    private function deferInternal(CommandDataInterface $command): void
    {
        if (!empty($command->getType()) && $command->getType()->isBatchPatch()) {
            /** @var BatchPatchCommandData $batchPatchCommand */
            $batchPatchCommand = $command;
            /**
             * @var IdAndChangeVector $value
             */
            foreach ($batchPatchCommand->getIds() as $value) {
                $this->addCommand($batchPatchCommand, $value->getId(), CommandType::patch(), $batchPatchCommand->getName());
            }
            return;
        }

        $this->addCommand($command, $command->getId(), $command->getType(), $command->getName());
    }

    private function addCommand(CommandDataInterface $command, string $id, CommandType $commandType, ?string $commandName): void
    {
        $this->deferredCommandsMap->put(
            $this->deferredCommandsMap->getIndexOrCreateNewFor($id, $commandType, $commandName),
            $command
        );

        $this->deferredCommandsMap->put(
            $this->deferredCommandsMap->getIndexOrCreateNewFor($id, CommandType::clientAnyCommand(), null),
            $command
        );

        if (!$command->getType()->isAttachmentPut() &&
            !$command->getType()->isAttachmentDelete() &&
            !$command->getType()->isAttachmentCopy() &&
            !$command->getType()->isAttachmentMove() &&
            !$command->getType()->isCounters() &&
            !$command->getType()->isTimeSeries() &&
            !$command->getType()->isTimeSeriesWithIncrements() &&
            !$command->getType()->isTimeSeriesCopy()
        ) {
            $this->deferredCommandsMap->put(
                $this->deferredCommandsMap->getIndexOrCreateNewFor($id, CommandType::clientModifyDocumentCommand(), null),
                $command
            );
        }
    }

    public function close(bool $isDisposing = true): void
    {
        if ($this->isDisposed) {
            return;
        }

        // todo: implement this
        EventHelper::invoke($this->onSessionClosing, $this, new SessionClosingEventArgs($this));

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

        if (empty($includes)) {
            return;
        }

        foreach ($includes as $item) {
            $newDocumentInfo = DocumentInfo::getNewDocumentInfo($item);
            if (JsonExtensions::tryGetConflict($newDocumentInfo->getMetadata())) {
                continue;
            }

            $this->includedDocumentsById->offsetSet($newDocumentInfo->getId(), $newDocumentInfo);
        }
    }

    public function registerRevisionIncludes(?array $revisionIncludes): void
    {
        if ($this->noTracking) {
            return;
        }

        if ($revisionIncludes == null || empty($revisionIncludes)) {
            return;
        }

        if ($this->includeRevisionsByChangeVector == null) {
            $this->includeRevisionsByChangeVector = new DocumentInfoArray() ;// new TreeMap<>(String::compareToIgnoreCase);
            $this->includeRevisionsByChangeVector->useKeysCaseInsensitive();
        }

        if ($this->includeRevisionsIdByDateTimeBefore == null) {
            $this->includeRevisionsIdByDateTimeBefore = new DocumentInfoDatesArray()  ;// new TreeMap<>(String::compareToIgnoreCase);
            $this->includeRevisionsIdByDateTimeBefore->useKeysCaseInsensitive();
        }

        foreach ($revisionIncludes as $json) {
            if (!is_array($json)) {
                continue;
            }

            $id = strval($json["Id"]);
            $changeVector = strval($json["ChangeVector"]);
            $beforeAsText = !empty($json["Before"]) ? strval($json["Before"]) : null;
            $dateTime = $beforeAsText != null ? NetISO8601Utils::fromString($beforeAsText) : null;
            $revision = $json["Revision"];

            $this->includeRevisionsByChangeVector[$changeVector] = DocumentInfo::getNewDocumentInfo($revision);

            if ($dateTime != null && $dateTime->getTimestamp() != 0 && StringUtils::isNotBlank($id)) {
                $map = new DocumentInfoDates();

                $documentInfo = new DocumentInfo();
                $documentInfo->setDocument($revision);
                $map->put($dateTime, $documentInfo);

                $this->includeRevisionsIdByDateTimeBefore[$id] = $map;
            }
        }
    }

    public function registerMissingIncludes(array $results, array $includes, ?StringArray $includePaths): void
    {
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
    }

    public function registerCounters(
        array                  $resultCounters,
        null|StringArray|array $ids = null,                 // String[]
        null|StringArray|array $countersToInclude = null,   // String[]
        bool                   $gotAll = false
    ): void
    {
        if (is_array($ids)) {
            $ids = StringArray::fromArray($ids);
        }
        if (is_array($countersToInclude)) {
            $countersToInclude = StringArray::fromArray($countersToInclude);
        }
        if ($this->noTracking) {
            return;
        }

        if (empty($resultCounters)) {
            if ($gotAll) {
                foreach ($ids as $id) {
                    $this->setGotAllCountersForDocument($id);
                }
                return;
            }
        } else {
            $this->registerCountersInternal($resultCounters, null, false, $gotAll);
        }

        $this->registerMissingCountersByIds($ids, $countersToInclude);
    }

    public function registerCountersFromQuery(
        array $resultCounters,      // String[]
        array $countersToInclude    // Map<String, String[]>
    ): void
    {
        if ($this->noTracking) {
            return;
        }

        if (count($resultCounters) == 0) {
            $this->setGotAllInCacheIfNeeded($countersToInclude);
        } else {
            $this->registerCountersInternal($resultCounters, $countersToInclude, true, false);
        }

        $this->registerMissingCounters($countersToInclude);
    }

    private function registerCountersInternal(
        array  $resultCounters,
        ?array $countersToInclude,   // Map<String, String[]>
        bool   $fromQueryResult,
        bool   $gotAll
    ): void
    {
        foreach ($resultCounters as $field => $value) {

            if (empty($value)) {
                continue;
            }

            $counters = null;

            if ($fromQueryResult) {
                if (!empty($countersToInclude)) {
                    $counters = array_key_exists($field, $countersToInclude) ? $countersToInclude[$field] : null;
                }
                $gotAll = $counters !== null && count($counters) == 0;
            }

            if (count($value) == 0 && !$gotAll) {
                if (!array_key_exists($field, $this->getCountersByDocId())) {
                    continue;
                }

                $cache = $this->getCountersByDocId()[$field];

                foreach ($counters as $counter) {
                    $index = array_search($counter, $cache[1]);
                    if ($index) {
                        unset($cache[1][$index]);
                    }
                }

                $this->getCountersByDocId()[$field] = $cache;
                continue;
            }

            $this->registerCountersForDocument($field, $gotAll, $value, $countersToInclude);
        }
    }

    private function registerCountersForDocument(
        ?string $id,
        bool    $gotAll,
        array   $counters,          // String[]
        ?array  $countersToInclude  // Map<String, String[]>
    ): void
    {
        if (array_key_exists($id, $this->getCountersByDocId())) {
            $cache = $this->getCountersByDocId()[$id];
        } else {
            $array = new ExtendedArrayObject();
            $array->setKeysCaseInsensitive(true);
            $cache = [$gotAll, $array];
        }

        $emptyCache = $cache[1] == null || count($cache[1]) == 0;
        $deletedCounters = $emptyCache
            ? []
            : ($countersToInclude === null || count($countersToInclude[$id]) == 0 ? array_keys($cache[1]->getArrayCopy()) : $countersToInclude[$id]);

        foreach ($counters as $counterJson) {
            $counterName = $counterJson !== null && array_key_exists('CounterName', $counterJson) ? $counterJson['CounterName'] : null;
            $totalValue = $counterJson !== null && array_key_exists('TotalValue', $counterJson) ? $counterJson['TotalValue'] : null;

            if ($counterName != null && $totalValue != null) {
                $cache[1][strval($counterName)] = intval($totalValue);
                if (($key = array_search($counterName, $deletedCounters)) !== false) {
                    unset($deletedCounters[$key]);
                }
            }
        }

        if (!empty($deletedCounters)) {
            foreach ($deletedCounters as $name) {
                if ($cache[1]->offsetExists($name)) {
                    unset($cache[1][$name]);
                }
            }
        }

        $cache[0] = $gotAll;
        $this->getCountersByDocId()[$id] = $cache;
    }

    private function setGotAllInCacheIfNeeded(array $countersToInclude): void
    {
        foreach ($countersToInclude as $key => $value) {
            if (count($value) > 0) {
                continue;
            }

            $this->setGotAllCountersForDocument($key);
        }
    }

    private
    function setGotAllCountersForDocument(?string $id): void
    {
        if (array_key_exists($id, $this->getCountersByDocId())) {
            $cache = $this->getCountersByDocId()[$id];
        } else {
            $array = new ExtendedArrayObject();
            $array->setKeysCaseInsensitive(true);
            $cache = [false, $array];
        }

        $cache[0] = true;
        $this->getCountersByDocId()[$id] = $cache;
    }

    private function registerMissingCounters(array $countersToInclude): void
    {
        if ($countersToInclude == null) {
            return;
        }

        foreach ($countersToInclude as $key => $counters) {
            if (!array_key_exists($key, $this->getCountersByDocId())) {
                $array = new ExtendedArrayObject();
                $array->setKeysCaseInsensitive(true);
                $cache = [false, $array];
            } else {
                $cache = $this->getCountersByDocId()[$key];
            }

            foreach ($counters as $counter) {
                if ($cache[1]->offsetExists($counter)) {
                    continue;
                }
                $cache[1][$counter] = null;
            }
            $this->getCountersByDocId()[$key] = $cache;
        }
    }

    private function registerMissingCountersByIds(StringArray $ids, ?StringArray $countersToInclude): void
    {
        if ($countersToInclude == null) {
            return;
        }

        foreach ($countersToInclude as $counter) {
            foreach ($ids as $id) {
                if (!array_key_exists($id, $this->getCountersByDocId())) {
                    $array = new ExtendedArrayObject();
                    $array->setKeysCaseInsensitive(true);
                    $cache = [false, $array];
                } else {
                    $cache = $this->getCountersByDocId()[$id];
                }

                if (!$cache[1]->offsetExists($counter)) {
                    $cache[1][$counter] = null;
                }

                $this->getCountersByDocId()[$id] = $cache;
            }
        }
    }


    public
    function registerTimeSeries(?array $resultTimeSeries): void
    {
        if ($this->noTracking || $resultTimeSeries == null) {
            return;
        }

        foreach ($resultTimeSeries as $field => $value) {
            if ($value == null) {
                continue;
            }

            $id = $field;

            if (!array_key_exists($id, $this->getTimeSeriesByDocId())) {
                $a = new ExtendedArrayObject();
                $a->useKeysCaseInsensitive();
                $this->getTimeSeriesByDocId()[$id] = $a;
            }

            $cache =  &$this->getTimeSeriesByDocId()[$id];

            if (!is_array($value)) {
                throw new IllegalStateException("Unable to read time series range results on document: '" . $id . "'.");
            }

            foreach ($value as $name => $innerValue) {
                if ($innerValue == null) {
                    continue;
                }

                if (!is_array($innerValue)) {
                    throw new IllegalStateException("Unable to read time series range results on document: '" . $id . "', time series: '" . $name . "'.");
                }

                foreach ($innerValue as $jsonRange) {
                    /** @var TimeSeriesRangeResult $newRange */
                    $newRange = self::parseTimeSeriesRangeResult($this->mapper, $jsonRange, $id, $name);
                    $this->addToCache($cache, $newRange, $name);
                }
            }
        }
    }

    private
    static function addToCache(
        ExtendedArrayObject    &$cache, // Map<String, List<TimeSeriesRangeResult>>
        ?TimeSeriesRangeResult $newRange,
        ?string                $name): void
    {
        if (!$cache->offsetExists($name) || empty($cache[$name])) {
            // no local ranges in cache for this series

            $item = new TimeSeriesRangeResultList();
            $item->append($newRange);
            $cache[$name] = $item;
            return;
        }

        $localRanges = $cache[$name];

        if (DatesComparator::compare(DatesComparator::leftDate($localRanges[0]->getFrom()), DatesComparator::rightDate($newRange->getTo())) > 0
            || DatesComparator::compare(DatesComparator::rightDate($localRanges[count($localRanges) - 1]->getTo()), DatesComparator::leftDate($newRange->getFrom())) < 0) {
            // the entire range [from, to] is out of cache bounds

            $index = DatesComparator::compare(DatesComparator::leftDate($localRanges[0]->getFrom()), DatesComparator::rightDate($newRange->getTo())) > 0 ? 0 : count($localRanges);
            $localRanges[$index] = $newRange;
            $cache[$name] = $localRanges;
            return;
        }

        $toRangeIndex = 0;
        $fromRangeIndex = -1;
        $rangeAlreadyInCache = false;

        for ($toRangeIndex = 0; $toRangeIndex < count($localRanges); $toRangeIndex++) {
            if (DatesComparator::compare(DatesComparator::leftDate($localRanges[$toRangeIndex]->getFrom()), DatesComparator::leftDate($newRange->getFrom())) <= 0) {
                if (DatesComparator::compare(DatesComparator::rightDate($localRanges[$toRangeIndex]->getTo()), DatesComparator::rightDate($newRange->getTo())) >= 0) {
                    $rangeAlreadyInCache = true;
                    break;
                }

                $fromRangeIndex = $toRangeIndex;
                continue;
            }

            if (DatesComparator::compare(DatesComparator::rightDate($localRanges[$toRangeIndex]->getTo()), DatesComparator::rightDate($newRange->getTo())) >= 0) {
                break;
            }
        }

        if ($rangeAlreadyInCache) {
            self::updateExistingRange($localRanges[$toRangeIndex], $newRange);
            $cache[$name] = $localRanges;
            return;
        }

        $mergedValues = self::mergeRanges($fromRangeIndex, $toRangeIndex, $localRanges, $newRange);
        $localRangesSet = TimeSeriesRangeResultList::fromArray($localRanges);
        self::addToCacheInternal($name, $newRange->getFrom(), $newRange->getTo(), $fromRangeIndex, $toRangeIndex, $localRangesSet, $cache, $mergedValues);
    }

    public
    static function addToCacheInternal(?string                   $timeseries,
                                       ?DateTimeInterface        $from,
                                       ?DateTimeInterface        $to,
                                       int                       $fromRangeIndex,
                                       int                       $toRangeIndex,
                                       TimeSeriesRangeResultList &$ranges,
                                       ExtendedArrayObject       &$cache, // Map<String, List<TimeSeriesRangeResult>>
                                       TimeSeriesEntryArray      $values): void
    {
        if ($fromRangeIndex == -1) {
            // didn't find a 'fromRange' => all ranges in cache start after 'from'

            if ($toRangeIndex == count($ranges)) {
                // the requested range [from, to] contains all the ranges that are in cache

                // e.g. if cache is : [[2,3], [4,5], [7, 10]]
                // and the requested range is : [1, 15]
                // after this action cache will be : [[1, 15]]

                $timeSeriesRangeResult = new TimeSeriesRangeResult();
                $timeSeriesRangeResult->setFrom($from);
                $timeSeriesRangeResult->setTo($to);
                $timeSeriesRangeResult->setEntries($values);

                $result = new TimeSeriesRangeResultList();
                $result->append($timeSeriesRangeResult);
                $cache[$timeseries] = $result;

                return;
            }

            if (DatesComparator::compare(DatesComparator::leftDate($ranges[$toRangeIndex]->getFrom()), DatesComparator::rightDate($to)) > 0) {
                // requested range ends before 'toRange' starts
                // remove all ranges that come before 'toRange' from cache
                // add the new range at the beginning of the list

                // e.g. if cache is : [[2,3], [4,5], [7,10]]
                // and the requested range is : [1,6]
                // after this action cache will be : [[1,6], [7,10]]

                $ranges->removeValues(0, $toRangeIndex);

                $timeSeriesRangeResult = new TimeSeriesRangeResult();
                $timeSeriesRangeResult->setFrom($from);
                $timeSeriesRangeResult->setTo($to);
                $timeSeriesRangeResult->setEntries($values);
                $ranges->prepend($timeSeriesRangeResult);

                $cache[$timeseries] = $ranges->getArrayCopy();
                return;
            }

            // the requested range ends inside 'toRange'
            // merge the result from server into 'toRange'
            // remove all ranges that come before 'toRange' from cache

            // e.g. if cache is : [[2,3], [4,5], [7,10]]
            // and the requested range is : [1,8]
            // after this action cache will be : [[1,10]]

            $ranges[$toRangeIndex]->setFrom($from);
            $ranges[$toRangeIndex]->setEntries($values);
            $ranges->removeValues(0, $toRangeIndex);

            $cache[$timeseries] = $ranges->getArrayCopy();
            return;
        }

        // found a 'fromRange'

        if ($toRangeIndex == count($ranges)) {
            // didn't find a 'toRange' => all the ranges in cache end before 'to'

            if (DatesComparator::compare(DatesComparator::rightDate($ranges[$fromRangeIndex]->getTo()), DatesComparator::leftDate($from)) < 0) {
                // requested range starts after 'fromRange' ends,
                // so it needs to be placed right after it
                // remove all the ranges that come after 'fromRange' from cache
                // add the merged values as a new range at the end of the list

                // e.g. if cache is : [[2,3], [5,6], [7,10]]
                // and the requested range is : [4,12]
                // then 'fromRange' is : [2,3]
                // after this action cache will be : [[2,3], [4,12]]

                $ranges->removeValues($fromRangeIndex + 1, count($ranges) - $fromRangeIndex - 1);
                $timeSeriesRangeResult = new TimeSeriesRangeResult();
                $timeSeriesRangeResult->setFrom($from);
                $timeSeriesRangeResult->setTo($to);
                $timeSeriesRangeResult->setEntries($values);

                $ranges->append($timeSeriesRangeResult);

                $cache[$timeseries] = $ranges->getArrayCopy();
                return;
            }

            // the requested range starts inside 'fromRange'
            // merge result into 'fromRange'
            // remove all the ranges from cache that come after 'fromRange'

            // e.g. if cache is : [[2,3], [4,6], [7,10]]
            // and the requested range is : [5,12]
            // then 'fromRange' is [4,6]
            // after this action cache will be : [[2,3], [4,12]]

            $ranges[$fromRangeIndex]->setTo($to);
            $ranges[$fromRangeIndex]->setEntries($values);
            $ranges->removeValues($fromRangeIndex + 1, count($ranges) - $fromRangeIndex - 1);

            $cache[$timeseries] = $ranges->getArrayCopy();
            return;
        }

        // found both 'fromRange' and 'toRange'
        // the requested range is inside cache bounds

        if (DatesComparator::compare(DatesComparator::rightDate($ranges[$fromRangeIndex]->getTo()), DatesComparator::leftDate($from)) < 0) {
            // requested range starts after 'fromRange' ends

            if (DatesComparator::compare(DatesComparator::leftDate($ranges[$toRangeIndex]->getFrom()), DatesComparator::rightDate($to)) > 0) {
                // requested range ends before 'toRange' starts

                // remove all ranges in between 'fromRange' and 'toRange'
                // place new range in between 'fromRange' and 'toRange'

                // e.g. if cache is : [[2,3], [5,6], [7,8], [10,12]]
                // and the requested range is : [4,9]
                // then 'fromRange' is [2,3] and 'toRange' is [10,12]
                // after this action cache will be : [[2,3], [4,9], [10,12]]

                $ranges->removeValues($fromRangeIndex + 1, $toRangeIndex - $fromRangeIndex - 1);

                $timeSeriesRangeResult = new TimeSeriesRangeResult();
                $timeSeriesRangeResult->setFrom($from);
                $timeSeriesRangeResult->setTo($to);
                $timeSeriesRangeResult->setEntries($values);

                $ranges->insertValue($fromRangeIndex + 1, $timeSeriesRangeResult);

                $cache[$timeseries] = $ranges->getArrayCopy();
                return;
            }

            // requested range ends inside 'toRange'

            // merge the new range into 'toRange'
            // remove all ranges in between 'fromRange' and 'toRange'

            // e.g. if cache is : [[2,3], [5,6], [7,10]]
            // and the requested range is : [4,9]
            // then 'fromRange' is [2,3] and 'toRange' is [7,10]
            // after this action cache will be : [[2,3], [4,10]]

            $ranges->removeValues($fromRangeIndex + 1, $toRangeIndex - $fromRangeIndex - 1);

            $ranges[$toRangeIndex]->setFrom($from);
            $ranges[$toRangeIndex]->setEntries($values);

            $cache[$timeseries] = $ranges->getArrayCopy();
            return;
        }

        // the requested range starts inside 'fromRange'

        if (DatesComparator::compare(DatesComparator::leftDate($ranges[$toRangeIndex]->getFrom()), DatesComparator::rightDate($to)) > 0) {
            // requested range ends before 'toRange' starts

            // remove all ranges in between 'fromRange' and 'toRange'
            // merge new range into 'fromRange'

            // e.g. if cache is : [[2,4], [5,6], [8,10]]
            // and the requested range is : [3,7]
            // then 'fromRange' is [2,4] and 'toRange' is [8,10]
            // after this action cache will be : [[2,7], [8,10]]

            $ranges[$fromRangeIndex]->setTo($to);
            $ranges[$fromRangeIndex]->setEntries($values);
            $ranges->removeValues($fromRangeIndex + 1, $toRangeIndex - $fromRangeIndex - 1);

            $cache[$timeseries] = $ranges->getArrayCopy();
            return;
        }

        // the requested range starts inside 'fromRange'
        // and ends inside 'toRange'

        // merge all ranges in between 'fromRange' and 'toRange'
        // into a single range [fromRange.From, toRange.To]

        // e.g. if cache is : [[2,4], [5,6], [8,10]]
        // and the requested range is : [3,9]
        // then 'fromRange' is [2,4] and 'toRange' is [8,10]
        // after this action cache will be : [[2,10]]

        $ranges[$fromRangeIndex]->setTo($ranges[$toRangeIndex]->getTo());
        $ranges[$fromRangeIndex]->setEntries($values);
        $ranges->removeRange($fromRangeIndex + 1, $toRangeIndex + 1);

        $cache[$timeseries] = $ranges->getArrayCopy();
    }

    private
    static function parseTimeSeriesRangeResult(EntityMapper $mapper, $jsonRange, $id, $databaseName): TimeSeriesRangeResult
    {
        return $mapper->denormalize($jsonRange, TimeSeriesRangeResult::class);
    }

    private
    static function mergeRanges(int $fromRangeIndex, int $toRangeIndex, TimeSeriesRangeResultList|array $localRanges, TimeSeriesRangeResult $newRange): TimeSeriesEntryArray
    {
        if (is_array($localRanges)) {
            $localRanges = TimeSeriesRangeResultList::fromArray($localRanges);
        }

        $mergedValues = new TimeSeriesEntryArray();

        if ($fromRangeIndex != -1 && $localRanges[$fromRangeIndex]->getTo() >= $newRange->getFrom()) {
            /** @var TimeSeriesEntry $val */
            foreach ($localRanges[$fromRangeIndex]->getEntries() as $val) {
                if ($val->getTimestamp() >= $newRange->getFrom()) {
                    break;
                }
                $mergedValues->append($val);
            }
        }

        $mergedValues->appendArrayValues($newRange->getEntries()->getArrayCopy());

        if ($toRangeIndex < count($localRanges)
            && DatesComparator::compare(DatesComparator::leftDate($localRanges[$toRangeIndex]->getFrom()), DatesComparator::rightDate($newRange->getTo())) <= 0) {
            /** @var TimeSeriesEntry $val */
            foreach ($localRanges[$toRangeIndex]->getEntries() as $val) {
                if ($val->getTimestamp() <= $newRange->getTo()) {
                    continue;
                }
                $mergedValues->append($val);
            }
        }

        return $mergedValues;
    }

    private
    static function updateExistingRange(TimeSeriesRangeResult &$localRange, TimeSeriesRangeResult $newRange): void
    {
        $newValues = new TimeSeriesEntryArray();
        $index = 0;
        for ($index = 0; $index < count($localRange->getEntries()); $index++) {
            if ($localRange->getEntries()[$index]->getTimestamp() >= $newRange->getFrom()) {
                break;
            }

            $newValues->append($localRange->getEntries()[$index]);
        }

        $newValues->appendArrayValues($newRange->getEntries()->getArrayCopy());

        for ($j = 0; $j < count($localRange->getEntries()); $j++) {
            if ($localRange->getEntries()[$j]->getTimestamp() <= $newRange->getTo()) {
                continue;
            }

            $newValues->append($localRange->getEntries()[$j]);
        }

        $localRange->setEntries($newValues);
    }


    public function hashCode(): int
    {
        return $this->hash;
    }

    /**
     * @throws ExceptionInterface
     */
    private function deserializeFromTransformer(?string $entityType, ?string $id, array $document, bool $trackEntity)
    {
        return $this->entityToJson->convertToEntity($entityType, $id, $document, $trackEntity);
    }


    public function checkIfIdAlreadyIncluded(?StringArray $ids, ?StringArray $includes): bool
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

  public function checkIfAllChangeVectorsAreAlreadyIncluded(StringArray|array $changeVectors): bool
  {
        if ($this->includeRevisionsByChangeVector == null) {
            return false;
        }

        foreach ($changeVectors as $cv) {
            if (!$this->includeRevisionsByChangeVector->offsetExists($cv)) {
                return false;
            }
        }

        return true;
    }

    public function checkIfRevisionByDateTimeBeforeAlreadyIncluded(string $id, DateTime $dateTime): bool
    {
        if ($this->includeRevisionsIdByDateTimeBefore == null) {
            return false;
        }

        if (!$this->includeRevisionsIdByDateTimeBefore->offsetExists($id)) {
            return false;
        }
        $dictionaryDateTimeToDocument = $this->includeRevisionsIdByDateTimeBefore->offsetGet($id);
        if ($dictionaryDateTimeToDocument != null) {
            return $dictionaryDateTimeToDocument->containsKey($dateTime);
        }

        return false;
    }



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

    /**
     * @template T
     *
     * @param T $entity
     * @param RavenCommand<GetDocumentsResult> $cmd
     * @param DocumentInfo $documentInfo
     *
     * @throws ExceptionInterface
     */
    protected function refreshInternal($entity, RavenCommand $cmd, DocumentInfo $documentInfo): void
    {
        /** @var GetDocumentsResult $result */
        $result = $cmd->getResult();
        $document = $result->getResults()[0];
        if ($document == null) {
            throw new IllegalStateException("Document '" . $documentInfo->getId() . "' no longer exists and was probably deleted");
        }

        $value = $document[DocumentsMetadata::KEY];
        $documentInfo->setMetadata($value);

        if ($documentInfo->getMetadata() != null) {
            $changeVector = $value[DocumentsMetadata::CHANGE_VECTOR];
            $documentInfo->setChangeVector($changeVector);
        }

        if ($documentInfo->getEntity() != null && !$this->noTracking) {
            $this->entityToJson->removeFromMissing($documentInfo->getEntity());
        }

        $documentInfo->setEntity($this->entityToJson->convertToEntity(get_class($entity), $documentInfo->getId(), $document, !$this->noTracking));
        $documentInfo->setDocument($document);

        $this->entityToJson->populateEntity($entity, $documentInfo->getId(), $document);

        $documentInfoById = $this->documentsById->getValue($documentInfo->getId());

        if ($documentInfoById != null) {
            $documentInfoById->setEntity($entity);
        }
    }

    protected static function getOperationResult(?string $className, mixed $result): mixed
    {
        if ($result == null) {
            return null;
        }

        if ($className == null) {
            return $result;
        }

        if (is_array($result)) {
            $reflect = new ReflectionClass($className);
            if ($reflect->isSubclassOf(ExtendedArrayObject::class)) {
                return ($className)::fromArray($result);
            } else {
                return JsonExtensions::getDefaultMapper()->denormalize($result, $className);
            }
        }

        if ($result instanceof $className) {
            return $result;
        };

        if ($result instanceof ObjectArray) {
            return $result->first();
        };

        throw new IllegalStateException("Unable to cast " . get_class($result) . " to " . $className);
    }

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

    public function onBeforeDeleteInvoke(BeforeDeleteEventArgs $eventArgs): void
    {
        EventHelper::invoke($this->onBeforeDelete, $this, $eventArgs);
    }

    public
    function onBeforeQueryInvoke(BeforeQueryEventArgs $eventArgs): void
    {
        EventHelper::invoke($this->onBeforeQuery, $this, $eventArgs);
    }

    public
    function onBeforeConversionToDocumentInvoke(string $id, object $entity): void
    {
        EventHelper::invoke(
            $this->onBeforeConversionToDocument,
            $this,
            new BeforeConversionToDocumentEventArgs($this, $id, $entity)
        );
    }

    public
    function onAfterConversionToDocumentInvoke(string $id, object $entity, &$document): void
    {
        if (!$this->onAfterConversionToDocument->isEmpty()) {
            $eventArgs = new AfterConversionToDocumentEventArgs($this, $id, $entity, $document);
            EventHelper::invoke($this->onAfterConversionToDocument, $this, $eventArgs);

            if ($eventArgs->getDocument() != null && $eventArgs->getDocument() != $document) {
                $document = $eventArgs->getDocument();
            }
        }
    }

    public
    function onBeforeConversionToEntityInvoke(?string $id, string $className, array &$document): void
    {
        if (!$this->onBeforeConversionToEntity->isEmpty()) {
            $eventArgs = new BeforeConversionToEntityEventArgs($this, $id, $className, $document);
            EventHelper::invoke($this->onBeforeConversionToEntity, $this, $eventArgs);

            if ($eventArgs->getDocument() != null && $eventArgs->getDocument() != $document) {
                $document = $eventArgs->getDocument();
            }
        }
    }

    public
    function onAfterConversionToEntityInvoke(?string $id, array $document, object $entity): void
    {
        $eventArgs = new AfterConversionToEntityEventArgs($this, $id, $entity, $document);
        EventHelper::invoke($this->onAfterConversionToEntity, $this, $eventArgs);
    }

    protected
    function processQueryParameters(?string $className, ?string $indexName, ?string $collectionName, DocumentConventions $conventions): array
    {
        $isIndex = StringUtils::isNotBlank($indexName);
        $isCollection = StringUtils::isNotEmpty($collectionName);

        if ($isIndex && $isCollection) {
            throw new IllegalStateException('Parameters indexName and collectionName are mutually exclusive. Please specify only one of them.');
        }

        if (!$isIndex && !$isCollection) {
            $collectionName = $conventions->getCollectionName($className) ?? DocumentsMetadata::ALL_DOCUMENTS_COLLECTION;
        }

        return [$indexName, $collectionName];
    }

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

    public
    function getTransactionMode(): TransactionMode
    {
        return $this->transactionMode;
    }

    public
    function setTransactionMode(TransactionMode $transactionMode): void
    {
        $this->transactionMode = $transactionMode;
    }

    public static function validateTimeSeriesName(?string $name): void
    {
        if (StringUtils::isEmpty($name)) {
            throw new IllegalArgumentException("Time Series name must contain at least one character");
        }

        if (StringUtils::startsWithIgnoreCase($name, Headers::INCREMENTAL_TIME_SERIES_PREFIX) && !str_contains($name, "@")) {
            throw new IllegalArgumentException("Time Series name cannot start with " . Headers::INCREMENTAL_TIME_SERIES_PREFIX . " prefix");
        }
    }

    public static function validateIncrementalTimeSeriesName(?string $name): void
    {
        if (StringUtils::isEmpty($name)) {
            throw new IllegalArgumentException("Incremental Time Series name must contain at least one character");
        }

        if (!StringUtils::startsWithIgnoreCase($name, Headers::INCREMENTAL_TIME_SERIES_PREFIX)) {
            throw new IllegalArgumentException("Time Series name must start with " . Headers::INCREMENTAL_TIME_SERIES_PREFIX . " prefix");
        }

        if (str_contains($name, "@")) {
            throw new IllegalArgumentException("Time Series from type Rollup cannot be Incremental");
        }
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

    private
    function removeIdFromKnownMissingIds(string $id): void
    {
        if (($key = array_search($id, $this->knownMissingIds)) !== false) {
            unset($this->knownMissingIds[$key]);
        }
    }
}
