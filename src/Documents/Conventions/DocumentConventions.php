<?php

namespace RavenDB\Documents\Conventions;

use Closure;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Type\TypedList;
use RavenDB\Type\StringList;
use InvalidArgumentException;
use RavenDB\Primitives\ClosureArray;
use RavenDB\Exceptions\RavenException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Extensions\EntityMapper;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\LoadBalanceBehavior;
use RavenDB\Http\ReadBalanceBehavior;
use RavenDB\Type\Duration;
use RavenDB\Utils\ClassUtils;
use RavenDB\Utils\StringUtils;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Throwable;
use RavenDB\Documents\Operations\Configuration\ClientConfiguration;

// !status: IN PROGRESS
class DocumentConventions
{
    private static ?DocumentConventions $defaultConventions = null;
    private static ?DocumentConventions $defaultForServerConventions = null;

    public static function getDefaultConventions(): DocumentConventions
    {
        if (self::$defaultConventions == null) {
            self::$defaultConventions = new DocumentConventions();
        }

        return self::$defaultConventions;
    }

    public static function getDefaultForServerConventions(): DocumentConventions
    {
        if (self::$defaultForServerConventions == null) {
            self::$defaultForServerConventions = new DocumentConventions();
        }

        return self::$defaultForServerConventions;
    }
//
//    static {
//        defaultConventions.freeze();
//        defaultForServerConventions._sendApplicationIdentifier = false;
//        defaultForServerConventions.freeze();
//    }
//

//    private static final Map<Class, String> _cachedDefaultTypeCollectionNames = new HashMap<>();
    protected static array $cachedDefaultTypeCollectionNames = [];

//    private final List<Tuple<Class, IValueForQueryConverter<Object>>> _listOfQueryValueToObjectConverters = new ArrayList<>();

    private ?ClosureArray $listOfRegisteredIdConventions = null;

    protected bool $frozen = false;
    private ?ClientConfiguration $originalConfiguration = null;

//    private final Map<Class, Field> _idPropertyCache = new HashMap<>();
    protected array $idPropertyCache = [];                  // @todo: check if this is ok?
    private bool $saveEnumsAsIntegers = false;

    protected ?string $identityPartsSeparator = null; // @todo: check if this is ok?
//    private char _identityPartsSeparator;

    protected bool $disableTopologyUpdates = false;

    protected bool $disableAtomicDocumentWritesInClusterWideTransaction = false;

    private ?Closure $shouldIgnoreEntityChanges = null;
    private ?Closure $findIdentityProperty = null;

    private ?Closure $transformClassCollectionNameToDocumentIdPrefix = null;
    private ?Closure $documentIdGenerator = null;
    private ?Closure $findIdentityPropertyNameFromCollectionName = null;
    private ?Closure $loadBalancerPerSessionContextSelector = null;

    private ?Closure $findCollectionName = null;

    private ?Closure $findPhpClassName = null;

    private ?Closure $findPhpClass = null;
    private ?Closure $findPhpClassByName = null;

    private bool $useOptimisticConcurrency = false;
    private bool $throwIfQueryPageSizeIsNotSet = false;
    protected int $maxNumberOfRequestsPerSession;

    private ?Duration $requestTimeout = null;
    private ?Duration $firstBroadcastAttemptTimeout = null;
    private ?Duration $secondBroadcastAttemptTimeout = null;
    private ?Duration $waitForIndexesAfterSaveChangesTimeout = null;
    private ?Duration $waitForReplicationAfterSaveChangesTimeout = null;
    private ?Duration $waitForNonStaleResultsTimeout = null;

    protected int $loadBalancerContextSeed = 0;
    protected LoadBalanceBehavior $loadBalanceBehavior;
    private ReadBalanceBehavior $readBalanceBehavior;
    protected int $maxHttpCacheSize;
    protected ?EntityMapper $entityMapper = null;
    private ?bool $useCompression = null;
    private bool $sendApplicationIdentifier = false;

//    private final BulkInsertConventions _bulkInsert;
//
//    private final AggressiveCacheConventions _aggressiveCache;
//
//    public AggressiveCacheConventions aggressiveCache() {
//        return _aggressiveCache;
//    }
//
//    public static class AggressiveCacheConventions {
//        private final DocumentConventions _conventions;
//        private final AggressiveCacheOptions _aggressiveCacheOptions;
//
//        public AggressiveCacheConventions(DocumentConventions conventions) {
//            _conventions = conventions;
//            _aggressiveCacheOptions = new AggressiveCacheOptions(Duration.ofDays(1), AggressiveCacheMode.TRACK_CHANGES);
//        }
//
//        public Duration getDuration() {
//            return _aggressiveCacheOptions.getDuration();
//        }
//
//        public void setDuration(Duration duration) {
//            _aggressiveCacheOptions.setDuration(duration);
//        }
//
//        public AggressiveCacheMode getMode() {
//            return _aggressiveCacheOptions.getMode();
//        }
//
//        public void setMode(AggressiveCacheMode mode) {
//            _aggressiveCacheOptions.setMode(mode);
//        }
//    }
//
//    public BulkInsertConventions bulkInsert() {
//        return _bulkInsert;
//    }
//
//    public static class BulkInsertConventions {
//        private final DocumentConventions _conventions;
//        private int _timeSeriesBatchSize;
//
//        public BulkInsertConventions(DocumentConventions conventions) {
//            _conventions = conventions;
//            _timeSeriesBatchSize = 1024;
//        }
//
//        public int getTimeSeriesBatchSize() {
//            return _timeSeriesBatchSize;
//        }
//
//        public void setTimeSeriesBatchSize(int batchSize) {
//            _conventions.assertNotFrozen();
//
//            if (batchSize <= 0) {
//                throw new IllegalArgumentException("BatchSize must be positive");
//            }
//            _timeSeriesBatchSize = batchSize;
//        }
//
//    }


    public function __construct()
    {
        $this->loadBalanceBehavior = LoadBalanceBehavior::none();
        $this->requestTimeout = new Duration();

        $this->listOfRegisteredIdConventions = new ClosureArray();
        // @todo: implement this constructor

        $this->readBalanceBehavior    = ReadBalanceBehavior::none();
        $this->findIdentityProperty   = function ($q) {
            return $q->getName() == 'id';
        };
        $this->identityPartsSeparator = '/';
        $this->findIdentityPropertyNameFromCollectionName = function($entityName) { return "Id"; };
        $this->findPhpClass = function(?string $id, array $doc): ?string {
            $metadata = array_key_exists(DocumentsMetadata::KEY, $doc) ? $doc[DocumentsMetadata::KEY] : null;
            if ($metadata != null) {
                $phpType = array_key_exists(DocumentsMetadata::RAVEN_PHP_TYPE, $metadata) ? $metadata[DocumentsMetadata::RAVEN_PHP_TYPE] : null;
                if ($phpType != null) {
                    return strval($phpType);
                }
            }
            return null;
        };

        $this->findPhpClassName = function ($entity) {
            return get_class($entity);
        };

        $this->findPhpClassByName = function($name) {
            try {
                if (!class_exists($name)) {
                    throw new RuntimeException('Class: ' . $name .  ' does not exists.');
                }

                return $name;
            } catch (Throwable $e) {
                throw new RavenException("Unable to find class by name = " . $name, $e);
            }
        };


        $this->transformClassCollectionNameToDocumentIdPrefix = Closure::fromCallable([$this, 'defaultTransformCollectionNameToDocumentIdPrefix']);

        $this->findCollectionName = Closure::fromCallable([$this, 'defaultGetCollectionName']);

        $this->maxNumberOfRequestsPerSession = 30;
//        _bulkInsert = new BulkInsertConventions(this);
        $this->maxHttpCacheSize = 128 * 1024 * 1024;

        $this->entityMapper = JsonExtensions::getDefaultEntityMapper();

//
//        _aggressiveCache = new AggressiveCacheConventions(this);
        $this->firstBroadcastAttemptTimeout = Duration::ofSeconds(5);
        $this->secondBroadcastAttemptTimeout = Duration::ofSeconds(30);

        $this->waitForIndexesAfterSaveChangesTimeout = Duration::ofSeconds(15);
        $this->waitForReplicationAfterSaveChangesTimeout = Duration::ofSeconds(15);
        $this->waitForNonStaleResultsTimeout = Duration::ofSeconds(15);

        $this->sendApplicationIdentifier = true;
    }

    public function hasExplicitlySetCompressionUsage(): bool
    {
        return $this->useCompression != null;
    }

    public function getRequestTimeout(): ?Duration
    {
        return $this->requestTimeout;
    }

    public function setRequestTimeout(?Duration $requestTimeout): void
    {
        $this->assertNotFrozen();
        $this->requestTimeout = $requestTimeout;
    }

    /**
     * Enables sending a unique application identifier to the RavenDB Server that is used for Client API usage tracking.
     * It allows RavenDB Server to issue performance hint notifications e.g. during robust topology update requests which could indicate Client API misuse impacting the overall performance
     * @return bool if option is enabled
     */
    public function isSendApplicationIdentifier(): bool
    {
        return $this->sendApplicationIdentifier;
    }

    /**
     * Enables sending a unique application identifier to the RavenDB Server that is used for Client API usage tracking.
     * It allows RavenDB Server to issue performance hint notifications e.g. during robust topology update requests which could indicate Client API misuse impacting the overall performance
     * @param bool $sendApplicationIdentifier if option should be enabled
     */
    public function setSendApplicationIdentifier(bool $sendApplicationIdentifier): void
    {
        $this->assertNotFrozen();
        $this->sendApplicationIdentifier = $sendApplicationIdentifier;
    }

    /**
     * Get the timeout for the second broadcast attempt.
     * Default: 30 seconds
     *
     * Upon failure of the first attempt the request executor will resend the command to all nodes simultaneously.
     *
     * @return ?Duration broadcast timeout
     */
    public function getSecondBroadcastAttemptTimeout(): ?Duration
    {
        return $this->secondBroadcastAttemptTimeout;
    }

    /**
     * Set the timeout for the second broadcast attempt.
     * Default: 30 seconds
     *
     * Upon failure of the first attempt the request executor will resend the command to all nodes simultaneously.
     *
     * @param ?Duration $secondBroadcastAttemptTimeout broadcast timeout
     *
     * @throws IllegalStateException
     */
    public function setSecondBroadcastAttemptTimeout(?Duration $secondBroadcastAttemptTimeout): void
    {
        $this->assertNotFrozen();
        $this->secondBroadcastAttemptTimeout = $secondBroadcastAttemptTimeout;
    }

    /**
     * Get the timeout for the first broadcast attempt.
     * Default: 5 seconds
     *
     * First attempt will send a single request to a selected node.
     *
     * @return ?Duration broadcast timeout
     */
    public function getFirstBroadcastAttemptTimeout(): ?Duration
    {
        return $this->firstBroadcastAttemptTimeout;
    }

    /**
     * Set the timeout for the first broadcast attempt.
     * Default: 5 seconds
     *
     * First attempt will send a single request to a selected node.
     *
     * @param ?Duration $firstBroadcastAttemptTimeout broadcast timeout
     *
     * @throws IllegalStateException
     */
    public function setFirstBroadcastAttemptTimeout(?Duration $firstBroadcastAttemptTimeout): void
    {
        $this->assertNotFrozen();
        $this->firstBroadcastAttemptTimeout = $firstBroadcastAttemptTimeout;
    }

    /**
     * Get the wait for indexes after save changes timeout
     * Default: 15 seconds
     *
     * @return Duration wait timeout
     */
    public function getWaitForIndexesAfterSaveChangesTimeout(): Duration
    {
        return $this->waitForIndexesAfterSaveChangesTimeout;
    }

    /**
     * Set the wait for indexes after save changes timeout
     * Default: 15 seconds
     * @param ?Duration $waitForIndexesAfterSaveChangesTimeout wait timeout
     */
    public function setWaitForIndexesAfterSaveChangesTimeout(?Duration $waitForIndexesAfterSaveChangesTimeout): void
    {
        $this->assertNotFrozen();
        $this->waitForIndexesAfterSaveChangesTimeout = $waitForIndexesAfterSaveChangesTimeout;
    }

    /**
     * Get the default timeout for DocumentSession waitForNonStaleResults methods.
     * Default: 15 seconds
     *
     * @return ?Duration wait timeout
     */
    public function getWaitForNonStaleResultsTimeout(): ?Duration
    {
        return $this->waitForNonStaleResultsTimeout;
    }

    /**
     * Sets the default timeout for DocumentSession waitForNonStaleResults methods.
     * @param ?Duration $waitForNonStaleResultsTimeout wait timeout
     */
    public function setWaitForNonStaleResultsTimeout(?Duration $waitForNonStaleResultsTimeout): void
    {
        $this->assertNotFrozen();
        $this->waitForNonStaleResultsTimeout = $waitForNonStaleResultsTimeout;
    }

    /**
     * Gets the default timeout for DocumentSession.advanced().waitForReplicationAfterSaveChanges method.
     * @return ?Duration wait timeout
     */
    public function getWaitForReplicationAfterSaveChangesTimeout(): ?Duration
    {
        return $this->waitForReplicationAfterSaveChangesTimeout;
    }

    /**
     * Sets the default timeout for DocumentSession.advanced().waitForReplicationAfterSaveChanges method.
     * @param ?Duration $waitForReplicationAfterSaveChangesTimeout wait timeout
     */
    public function setWaitForReplicationAfterSaveChangesTimeout(?Duration $waitForReplicationAfterSaveChangesTimeout): void
    {
        $this->assertNotFrozen();
        $this->waitForReplicationAfterSaveChangesTimeout = $waitForReplicationAfterSaveChangesTimeout;
    }

    public function isUseCompression(): bool
    {
        if ($this->useCompression == null) {
            return true;
        }
        return $this->useCompression;
    }

    public function setUseCompression(bool $useCompression): void
    {
        $this->assertNotFrozen();
        $this->useCompression = $useCompression;
    }

    public function & getEntityMapper(): EntityMapper
    {
        return $this->entityMapper;
    }

    public function setEntityMapper(EntityMapper $mapper): void
    {
        $this->entityMapper = $mapper;
    }

    public function getReadBalanceBehavior(): ReadBalanceBehavior
    {
        return $this->readBalanceBehavior;
    }

    public function setReadBalanceBehavior(ReadBalanceBehavior $readBalanceBehavior): void
    {
        $this->assertNotFrozen();
        $this->readBalanceBehavior = $readBalanceBehavior;
    }

    public function getLoadBalancerContextSeed(): int
    {
        return $this->loadBalancerContextSeed;
    }

    /**
     * @throws IllegalStateException
     */
    public function setLoadBalancerContextSeed(int $seed): void
    {
        $this->assertNotFrozen();
        $this->loadBalancerContextSeed = $seed;
    }

    /**
     * We have to make this check so if admin activated this, but client code did not provide the selector,
     * it is still disabled. Relevant if we have multiple clients / versions at once.
     *
     * @return LoadBalanceBehavior load balance behavior
     */
    public function getLoadBalanceBehavior(): LoadBalanceBehavior
    {
        return $this->loadBalanceBehavior;
    }

    /**
     * @throws IllegalStateException
     */
    public function setLoadBalanceBehavior(LoadBalanceBehavior $loadBalanceBehavior): void
    {
        $this->assertNotFrozen();
        $this->loadBalanceBehavior = $loadBalanceBehavior;
    }

    /**
     * @return ?Closure Gets the function that allow to specialize the topology
     *  selection for a particular session. Used in load balancing
     *  scenarios
     */
    public function getLoadBalancerPerSessionContextSelector(): ?Closure
    {
        return $this->loadBalancerPerSessionContextSelector;
    }

    /**
     * Sets the function that allow to specialize the topology
     *  selection for a particular session. Used in load balancing
     *  scenarios
     *
     * @param Closure $loadBalancerPerSessionContextSelector selector to use
     */
    public function setLoadBalancerPerSessionContextSelector(Closure $loadBalancerPerSessionContextSelector): void
    {
        $this->loadBalancerPerSessionContextSelector = $loadBalancerPerSessionContextSelector;
    }

    public function getMaxHttpCacheSize(): int
    {
        return $this->maxHttpCacheSize;
    }

    /**
     * @throws IllegalStateException
     */
    public function setMaxHttpCacheSize(int $maxHttpCacheSize): void
    {
        $this->assertNotFrozen();
        $this->maxHttpCacheSize = $maxHttpCacheSize;
    }

    public function getMaxNumberOfRequestsPerSession(): int
    {
        return $this->maxNumberOfRequestsPerSession;
    }

    /**
     * @throws IllegalStateException
     */
    public function setMaxNumberOfRequestsPerSession(int $maxNumberOfRequestsPerSession): void
    {
        $this->assertNotFrozen();
        $this->maxNumberOfRequestsPerSession = $maxNumberOfRequestsPerSession;
    }

    /**
     * If set to 'true' then it will throw an exception when any query is performed (in session)
     * without explicit page size set.
     * This can be useful for development purposes to pinpoint all the possible performance bottlenecks
     * since from 4.0 there is no limitation for number of results returned from server.
     *
     * @return bool - true if should we throw if page size is not set
     */
    public function isThrowIfQueryPageSizeIsNotSet(): bool
    {
        return $this->throwIfQueryPageSizeIsNotSet;
    }

    /**
     * If set to 'true' then it will throw an exception when any query is performed (in session)
     * without explicit page size set.
     * This can be useful for development purposes to pinpoint all the possible performance bottlenecks
     * since from 4.0 there is no limitation for number of results returned from server.
     *
     * @param bool $throwIfQueryPageSizeIsNotSet value to set
     *
     * @throws IllegalStateException
     */
    public function setThrowIfQueryPageSizeIsNotSet(bool $throwIfQueryPageSizeIsNotSet): void
    {
        $this->assertNotFrozen();
        $this->throwIfQueryPageSizeIsNotSet = $throwIfQueryPageSizeIsNotSet;
    }

    /**
     * Whether UseOptimisticConcurrency is set to true by default for all opened sessions
     *
     * @return bool - true if optimistic concurrency is enabled
     */
    public function isUseOptimisticConcurrency(): bool
    {
        return $this->useOptimisticConcurrency;
    }

    /**
     * Whether UseOptimisticConcurrency is set to true by default for all opened sessions
     *
     * @param bool $useOptimisticConcurrency value to set
     *
     * @throws IllegalStateException
     */
    public function setUseOptimisticConcurrency(bool $useOptimisticConcurrency): void
    {
        $this->assertNotFrozen();
        $this->useOptimisticConcurrency = $useOptimisticConcurrency;
    }


    public function getFindPhpClass(): Closure
    {
        return $this->findPhpClass;
    }

    public function setFindPhpClass(Closure $findPhpClass): void
    {
        $this->assertNotFrozen();
        $this->findPhpClass = $findPhpClass;
    }

    public function getFindPhpClassName(): Closure
    {
        return $this->findPhpClassName;
    }

    public function setFindPhpClassName(Closure $findPhpClassName): void
    {
        $this->assertNotFrozen();
        $this->findPhpClassName = $findPhpClassName;
    }

    public function getFindCollectionName(): Closure
    {
        return $this->findCollectionName;
    }

    public function setFindCollectionName(Closure $findCollectionName): void
    {
        $this->assertNotFrozen();
        $this->findCollectionName = $findCollectionName;
    }

    public function getFindPhpClassByName(): Closure
    {
        return $this->findPhpClassByName;
    }

    public function setFindPhpClassByName(Closure $findPhpClassByName): void
    {
        $this->findPhpClassByName = $findPhpClassByName;
    }

    public function getFindIdentityPropertyNameFromCollectionName(): Closure
    {
        return $this->findIdentityPropertyNameFromCollectionName;
    }

    public function setFindIdentityPropertyNameFromCollectionName(Closure $findIdentityPropertyNameFromCollectionName): void
    {
        $this->assertNotFrozen();
        $this->findIdentityPropertyNameFromCollectionName = $findIdentityPropertyNameFromCollectionName;
    }

    public function getDocumentIdGenerator(): ?Closure
    {
        return $this->documentIdGenerator;
    }

    public function setDocumentIdGenerator(Closure $documentIdGenerator): void
    {
        $this->assertNotFrozen();
        $this->documentIdGenerator = $documentIdGenerator;
    }


    /**
     *  Translates the types collection name to the document id prefix
     *
     * @return Closure translation function
     */
    public function getTransformClassCollectionNameToDocumentIdPrefix(): ?Closure
    {
        return $this->transformClassCollectionNameToDocumentIdPrefix;
    }

    /**
     *  Translates the types collection name to the document id prefix
     *
     * @param ?Closure $transformClassCollectionNameToDocumentIdPrefix value to set
     */
    public function setTransformClassCollectionNameToDocumentIdPrefix(?Closure $transformClassCollectionNameToDocumentIdPrefix): void
    {
        $this->assertNotFrozen();
        $this->transformClassCollectionNameToDocumentIdPrefix = $transformClassCollectionNameToDocumentIdPrefix;
    }

    public function getFindIdentityProperty(): ?Closure
    {
        return $this->findIdentityProperty;
    }

    public function setFindIdentityProperty(?Closure $findIdentityProperty): void
    {
        $this->assertNotFrozen();
        $this->findIdentityProperty = $findIdentityProperty;
    }

    public function getShouldIgnoreEntityChanges(): ?Closure
    {
        return $this->shouldIgnoreEntityChanges;
    }

    public function setShouldIgnoreEntityChanges(Closure $shouldIgnoreEntityChanges): void
    {
        $this->assertNotFrozen();
        $this->shouldIgnoreEntityChanges = $shouldIgnoreEntityChanges;
    }

    public function isDisableTopologyUpdates(): bool
    {
        return $this->disableTopologyUpdates;
    }

    /**
     * @throws IllegalStateException
     */
    public function setDisableTopologyUpdates(bool $disableTopologyUpdates): void
    {
        $this->assertNotFrozen();
        $this->disableTopologyUpdates = $disableTopologyUpdates;
    }

    public function getIdentityPartsSeparator(): string
    {
        return $this->identityPartsSeparator;
    }

    /**
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     */
    public function setIdentityPartsSeparator(string $identityPartsSeparator): void
    {
        $this->assertNotFrozen();

        if ($identityPartsSeparator == '|') {
            throw new InvalidArgumentException("Cannot set identity parts separator to '|'");
        }

        $this->identityPartsSeparator = $identityPartsSeparator;
    }

//    /**
//     * Saves Enums as integers and instruct the Linq provider to query enums as integer values.
//     * @return true if we should save enums as integers
//     */
//    public boolean isSaveEnumsAsIntegers() {
//        return _saveEnumsAsIntegers;
//    }
//
//    /**
//     * Saves Enums as integers and instruct the Linq provider to query enums as integer values.
//     * @param saveEnumsAsIntegers value to set
//     */
//    public void setSaveEnumsAsIntegers(boolean saveEnumsAsIntegers) {
//        assertNotFrozen();
//        this._saveEnumsAsIntegers = saveEnumsAsIntegers;
//    }

    /**
     * Default method used when finding a collection name for a type
     *
     * @param string $className Class
     *
     * @return string Default collection name for class
     *
     * @throws IllegalStateException|ReflectionException
     */
    public static function defaultGetCollectionName(string $className): string
    {
        $result = null;
        if (array_key_exists($className, self::$cachedDefaultTypeCollectionNames)) {
            $result = self::$cachedDefaultTypeCollectionNames[$className];

            if ($result != null) {
                return $result;
            }
        }

        $reflectionClass = new ReflectionClass($className);

        // we want to reject queries and other operations on abstract types, because you usually
        // want to use them for polymorphic queries, and that require the conventions to be
        // applied properly, so we reject the behavior and hint to the user explicitly
        if ($reflectionClass->isInterface()) {
            throw new IllegalStateException(
                "Cannot find collection name for interface " . $className .
                ", only concrete classes are supported. Did you forget to customize conventions->findCollectionName?"
            );
        }

        if ($reflectionClass->isAbstract()) {
            throw new IllegalStateException(
                "Cannot find collection name for abstract class " . $className .
                ", only concrete class are supported. Did you forget to customize conventions->findCollectionName?"
            );

        }

        $result = StringUtils::pluralize(ClassUtils::getSimpleClassName($className));

        self::$cachedDefaultTypeCollectionNames[$className] = $result;

        return $result;
    }

    /**
     * Gets the collection name for a given type.
     *
     * @param string|object|null $entity
     *
     * @return string|null
     *
     * @throws ReflectionException
     */
    public function getCollectionName(string|object|null $entity): ?string
    {
        if (empty($entity)) {
            return null;
        }

        $className = $entity;
        if (is_object($entity)) {
            $className = get_class($entity);
        }

        return $this->getCollectionNameForClass($className);
    }

    /**
     * @throws ReflectionException
     * @throws IllegalStateException
     */
    public function getCollectionNameForClass(string $className): string
    {
        $collectionName = null;
        if (!empty($this->findCollectionName)) {
            $f     = $this->findCollectionName;
            $collectionName = $f($className);
        }

        if ($collectionName != null) {
            return $collectionName;
        }

        return $this->defaultGetCollectionName($className);
    }

    /**
     * Generates the document id.
     *
     * @param string      $databaseName Database name
     * @param Object|null $entity       Entity
     *
     * @return string document id
     */
    public function generateDocumentId(string $databaseName, ?object $entity): string
    {
        $className = get_class($entity);
        foreach ($this->listOfRegisteredIdConventions as $keyClassName => $listOfRegisteredIdConvention) {
            if (is_a($className, $keyClassName, true)) {
                return $listOfRegisteredIdConvention($databaseName, $entity);
            }
        }

        $generator = $this->documentIdGenerator;
        return $generator($databaseName, $entity);
    }

    /**
     * Register an id convention for a single type (and all of its derived types.
     * Note that you can still fall back to the DocumentIdGenerator if you want.
     *
     * @param ?string $className Entity class
     * @param closure $function  Function to use
     *
     * @return DocumentConventions document conventions
     */
    public function registerIdConvention(?string $className, Closure $function): DocumentConventions
    {
        $this->assertNotFrozen();

        // remove exact class id convention
        if ($this->listOfRegisteredIdConventions->offsetExists($className)) {
            unset($this->listOfRegisteredIdConventions[$className]);
        }

        // if some class can handle given class id convention, we should replace it in database
        foreach ($this->listOfRegisteredIdConventions as $parentClass => $convention) {
            if (is_a($className, $parentClass, true)) {
                $className = $parentClass;
                break;
            }
        }

        $this->listOfRegisteredIdConventions->offsetSet($className, $function);

        return $this;
    }


    /**
     * Get the php class (if exists) from the document
     * @param ?string $id document id
     * @param array $document document to get java class from
     * @return ?string php class
     */
    public function getPhpClass(?string $id, array $document): ?string
    {
        $f = $this->findPhpClass;
        return $f($id, $document);
    }

    /**
     * Get the class instance by its name
     * @param string|null $name class name
     * @return ?string php class
     */
    public function getPhpClassByName(?string $name): ?string
    {
        $f = $this->findPhpClassByName;
        return $f($name);
    }

    /**
     * Get the PHP class name to be stored in the entity metadata
     *
     * @param object $entity Entity
     *
     * @return string|null PHP class name
     */
    public function getPhpClassName(object $entity): ?string
    {
        $f = $this->findPhpClassName;
        return $f($entity);
    }

    /**
     * EXPERT: Disable automatic atomic writes with cluster write transactions. If set to 'true', will only consider explicitly
     * added compare exchange values to validate cluster wide transactions.
     *
     * @return bool disable atomic writes
     */
    public function getDisableAtomicDocumentWritesInClusterWideTransaction(): bool
    {
        return $this->disableAtomicDocumentWritesInClusterWideTransaction;
    }

    /**
     * EXPERT: Disable automatic atomic writes with cluster write transactions. If set to 'true', will only consider explicitly
     * added compare exchange values to validate cluster wide transactions.
     *
     * @param bool $value disable atomic writes
     */
    public function setDisableAtomicDocumentWritesInClusterWideTransaction(bool $value): void
    {
        $this->assertNotFrozen();
        $this->disableAtomicDocumentWritesInClusterWideTransaction = $value;
    }

    // /**
    // * Clone the current conventions to a new instance
    // */
    // @SuppressWarnings("MethodDoesntCallSuperMethod")
    // public DocumentConventions clone() {
    // DocumentConventions cloned = new DocumentConventions();
    // cloned._listOfRegisteredIdConventions = new ArrayList<>(_listOfRegisteredIdConventions);
    // cloned._frozen = _frozen;
    // cloned._shouldIgnoreEntityChanges = _shouldIgnoreEntityChanges;
    // cloned._originalConfiguration = _originalConfiguration;
    // cloned._saveEnumsAsIntegers = _saveEnumsAsIntegers;
    // cloned._identityPartsSeparator = _identityPartsSeparator;
    // cloned._disableTopologyUpdates = _disableTopologyUpdates;
    // cloned._findIdentityProperty = _findIdentityProperty;
    // cloned._transformClassCollectionNameToDocumentIdPrefix = _transformClassCollectionNameToDocumentIdPrefix;
    // cloned._documentIdGenerator = _documentIdGenerator;
    // cloned._findIdentityPropertyNameFromCollectionName = _findIdentityPropertyNameFromCollectionName;
    // cloned._findCollectionName = _findCollectionName;
    // cloned._findJavaClassName = _findJavaClassName;
    // cloned._findJavaClass = _findJavaClass;
    // cloned._findJavaClassByName = _findJavaClassByName;
    // cloned._useOptimisticConcurrency = _useOptimisticConcurrency;
    // cloned._throwIfQueryPageSizeIsNotSet = _throwIfQueryPageSizeIsNotSet;
    // cloned._maxNumberOfRequestsPerSession = _maxNumberOfRequestsPerSession;
    // cloned._loadBalancerPerSessionContextSelector = _loadBalancerPerSessionContextSelector;
    // cloned._readBalanceBehavior = _readBalanceBehavior;
    // cloned._loadBalanceBehavior = _loadBalanceBehavior;
    // cloned._maxHttpCacheSize = _maxHttpCacheSize;
    // cloned._entityMapper = _entityMapper;
    // cloned._useCompression = _useCompression;
    // return cloned;
    // }
    //
    // private static Field getField(Class<? > clazz, String name) {
//        Field field = null;
//        while (clazz != null && field == null) {
//            try {
//                field = clazz.getDeclaredField(name);
//            } catch (Exception ignored) {
//            }
//            clazz = clazz.getSuperclass();
//        }
//        return field;
//    }

    /**
     *  Gets the identity property.
     */
    public function getIdentityProperty(?string $className): ?string
    {
        if (!$className) {
            return null;
        }

        if (array_key_exists($className, $this->idPropertyCache)) {
            $info = $this->idPropertyCache[$className];
            if ($info !== null) {
                return $info;
            }
        }


        try {
            $idField = null;

            $reflectionClass      = new ReflectionClass($className);
            $findIdentityProperty = $this->findIdentityProperty;
            foreach ($reflectionClass->getProperties() as $property) {
                if ($findIdentityProperty($property)) {
                    $idField = $property->getName();
                    break;
                }
            }

            $this->idPropertyCache[$className] = $idField;

            return $idField;
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage());
        }
    }


    public function updateFrom(?ClientConfiguration $configuration): void
    {
        if ($configuration == null) {
            return;
        }

        if ($configuration->isDisabled() && $this->originalConfiguration == null) { // nothing to do
            return;
        }

        if ($configuration->isDisabled() && $this->originalConfiguration != null) { // need to revert to original values
            $this->maxNumberOfRequestsPerSession = $this->originalConfiguration->getMaxNumberOfRequestsPerSession() ?? $this->maxNumberOfRequestsPerSession;
            $this->readBalanceBehavior           = $this->originalConfiguration->getReadBalanceBehavior() ?? $this->readBalanceBehavior;
            $this->identityPartsSeparator        = $this->originalConfiguration->getIdentityPartsSeparator() ?? $this->identityPartsSeparator;
            $this->loadBalanceBehavior           = $this->originalConfiguration->getLoadBalanceBehavior() ?? $this->loadBalanceBehavior;
            $this->loadBalancerContextSeed       = $this->originalConfiguration->getLoadBalancerContextSeed() ?? $this->loadBalancerContextSeed;

            $this->originalConfiguration = null;
            return;
        }

        if ($this->originalConfiguration == null) {
            $this->originalConfiguration = new ClientConfiguration();
            $this->originalConfiguration->setEtag(-1);
            $this->originalConfiguration->setMaxNumberOfRequestsPerSession($this->maxNumberOfRequestsPerSession);
            $this->originalConfiguration->setReadBalanceBehavior($this->readBalanceBehavior);
            $this->originalConfiguration->setIdentityPartsSeparator($this->identityPartsSeparator);
            $this->originalConfiguration->setLoadBalanceBehavior($this->loadBalanceBehavior);
            $this->originalConfiguration->setLoadBalancerContextSeed($this->loadBalancerContextSeed);
        }

        $this->maxNumberOfRequestsPerSession =
            $configuration->getMaxNumberOfRequestsPerSession() ??
            $this->originalConfiguration->getMaxNumberOfRequestsPerSession() ??
            $this->maxNumberOfRequestsPerSession;
        $this->readBalanceBehavior           =
            $configuration->getReadBalanceBehavior() ??
            $this->originalConfiguration->getReadBalanceBehavior() ??
            $this->readBalanceBehavior;
        $this->loadBalanceBehavior           =
            $configuration->getLoadBalanceBehavior() ??
            $this->originalConfiguration->getLoadBalanceBehavior() ??
            $this->loadBalanceBehavior;
        $this->loadBalancerContextSeed       =
            $configuration->getLoadBalancerContextSeed() ??
            $this->originalConfiguration->getLoadBalancerContextSeed() ??
            $this->loadBalancerContextSeed;
        $this->identityPartsSeparator        =
            $configuration->getIdentityPartsSeparator() ??
            $this->originalConfiguration->getIdentityPartsSeparator() ??
            $this->identityPartsSeparator;
    }

    public static function defaultTransformCollectionNameToDocumentIdPrefix(?string $collectionName): string
    {
        $upperCount = strlen($collectionName) - similar_text($collectionName, strtolower($collectionName));

        if ($upperCount <= 1) {
            return strtolower($collectionName);
        }

        // multiple capital letters, so probably something that we want to preserve caps on.
        return $collectionName;
    }

//    @SuppressWarnings("unchecked")
//    public <T> void registerQueryValueConverter(Class<T> clazz, IValueForQueryConverter<T> converter) {
//        assertNotFrozen();
//
//        int index;
//        for (index = 0; index < _listOfQueryValueToObjectConverters.size(); index++) {
//            Tuple<Class, IValueForQueryConverter<Object>> entry = _listOfQueryValueToObjectConverters.get(index);
//            if (entry.first.isAssignableFrom(clazz)) {
//                break;
//            }
//        }
//
//        _listOfQueryValueToObjectConverters.add(index, Tuple.create(clazz, (fieldName, value, forRange, stringValue) -> {
//            if (clazz.isInstance(value)) {
//                return converter.tryConvertValueForQuery(fieldName, (T) value, forRange, stringValue);
//            }
//            stringValue.value = null;
//            return false;
//        }));
//    }
//
//    public boolean tryConvertValueToObjectForQuery(String fieldName, Object value, boolean forRange, Reference<Object> strValue) {
//        for (Tuple<Class, IValueForQueryConverter<Object>> queryValueConverter : _listOfQueryValueToObjectConverters) {
//            if (!queryValueConverter.first.isInstance(value)) {
//                continue;
//            }
//
//            return queryValueConverter.second.tryConvertValueForQuery(fieldName, value, forRange, strValue);
//        }
//
//        strValue.value = null;
//        return false;
//    }

    public function freeze(): void
    {
        $this->frozen = true;
    }

    /**
     * @throws IllegalStateException
     */
    private function assertNotFrozen(): void
    {
        if ($this->frozen) {
            throw new IllegalStateException(
                "Conventions has been frozen after documentStore.initialize() " .
                "and no changes can be applied to them."
            );
        }
    }
}
