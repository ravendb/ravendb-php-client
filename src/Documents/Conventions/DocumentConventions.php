<?php

namespace RavenDB\Documents\Conventions;

use Closure;
use InvalidArgumentException;
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

// !status: IN PROGRESS
class DocumentConventions
{
    private static ?DocumentConventions $defaultConventions = null;
    private static ?DocumentConventions $defaultForServerConventions = null;

    public static function getDefaultConventions(): DocumentConventions
    {
        if (self::$defaultConventions == null) {
            self::$defaultConventions =  new DocumentConventions();
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
    protected array $cachedDefaultTypeCollectionNames = []; // @todo: check if this is ok

//    private final List<Tuple<Class, IValueForQueryConverter<Object>>> _listOfQueryValueToObjectConverters = new ArrayList<>();
//
//    private List<Tuple<Class, BiFunction<String, Object, String>>> _listOfRegisteredIdConventions = new ArrayList<>();

    protected bool $frozen = false;
//    private ClientConfiguration _originalConfiguration;

//    private final Map<Class, Field> _idPropertyCache = new HashMap<>();
    protected array $idPropertyCache = []; // @todo: check if this is ok?
//    private boolean _saveEnumsAsIntegers;

    protected ?string $identityPartsSeparator = null; // @todo: check if this is ok?
//    private char _identityPartsSeparator;

    protected bool $disableTopologyUpdates = false;

    protected bool $disableAtomicDocumentWritesInClusterWideTransaction = false;

    private ?ShouldIgnoreEntityChangesInterface $shouldIgnoreEntityChanges = null;
    private ?Closure $findIdentityProperty = null;

    private ?Closure $transformClassCollectionNameToDocumentIdPrefix = null;
    private ?Closure $documentIdGenerator = null;
    private ?Closure $findIdentityPropertyNameFromCollectionName = null;
    private ?Closure $loadBalancerPerSessionContextSelector = null;

    private ?string $findCollectionName = null;

    private ?Closure $findJavaClassName = null;
    private ?Closure $findJavaClass = null;
    private ?Closure $findJavaClassByName = null;

    private bool $useOptimisticConcurrency = false;
    private bool $throwIfQueryPageSizeIsNotSet = false;
    protected int $maxNumberOfRequestsPerSession;

    private Duration $requestTimeout;
    private ?Duration $firstBroadcastAttemptTimeout = null;
    private ?Duration $secondBroadcastAttemptTimeout = null;
    private Duration $waitForIndexesAfterSaveChangesTimeout;
    private Duration $waitForReplicationAfterSaveChangesTimeout;
    private Duration $waitForNonStaleResultsTimeout;

    protected int $loadBalancerContextSeed = 0;
    protected LoadBalanceBehavior $loadBalanceBehavior;
    private ReadBalanceBehavior $readBalanceBehavior;
    protected int $maxHttpCacheSize;
    protected EntityMapper $entityMapper;
//    private Boolean _useCompression;
//    private boolean _sendApplicationIdentifier;
//
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

        // @todo: implement this constructor

        $this->readBalanceBehavior = ReadBalanceBehavior::none();
//        _findIdentityProperty = q -> q.getName().equals("id");
        $this->identityPartsSeparator = '/';
//        _findIdentityPropertyNameFromCollectionName = entityName -> "Id";
//        _findJavaClass = (String id, ObjectNode doc) -> {
//            JsonNode metadata = doc.get(Constants.Documents.Metadata.KEY);
//            if (metadata != null) {
//                TextNode javaType = (TextNode) metadata.get(Constants.Documents.Metadata.RAVEN_JAVA_TYPE);
//                if (javaType != null) {
//                    return javaType.asText();
//                }
//            }
//
//            return null;
//        };
//        _findJavaClassName = type -> ReflectionUtil.getFullNameWithoutVersionInformation(type);
//        _findJavaClassByName = name -> {
//            try {
//                return Class.forName(name);
//            } catch (ClassNotFoundException e) {
//                throw new RavenException("Unable to find class by name = " + name, e);
//            }
//        };
//        _transformClassCollectionNameToDocumentIdPrefix =
//          collectionName -> defaultTransformCollectionNameToDocumentIdPrefix(collectionName);
//
        $this->findCollectionName = 'defaultGetCollectionName';
//
        $this->maxNumberOfRequestsPerSession = 30;
//        _bulkInsert = new BulkInsertConventions(this);
        $this->maxHttpCacheSize = 128 * 1024 * 1024;

        $this->entityMapper = JsonExtensions::getDefaultEntityMapper();

//
//        _aggressiveCache = new AggressiveCacheConventions(this);
//        _firstBroadcastAttemptTimeout = Duration.ofSeconds(5);
//        _secondBroadcastAttemptTimeout = Duration.ofSeconds(30);
//
//        _waitForIndexesAfterSaveChangesTimeout = Duration.ofSeconds(15);
//        _waitForReplicationAfterSaveChangesTimeout = Duration.ofSeconds(15);
//        _waitForNonStaleResultsTimeout = Duration.ofSeconds(15);
//
//        _sendApplicationIdentifier = true;
    }


//    public boolean hasExplicitlySetCompressionUsage() {
//        return _useCompression != null;
//    }
//
    public function getRequestTimeout(): Duration
    {
        return $this->requestTimeout;
    }

    public function setRequestTimeout(Duration $requestTimeout): void
    {
        $this->assertNotFrozen();
        $this->requestTimeout = $requestTimeout;
    }

//    /**
//     * Enables sending a unique application identifier to the RavenDB Server that is used for Client API usage tracking.
//     * It allows RavenDB Server to issue performance hint notifications e.g. during robust topology update requests which could indicate Client API misuse impacting the overall performance
//     * @return if option is enabled
//     */
//    public boolean isSendApplicationIdentifier() {
//        return _sendApplicationIdentifier;
//    }
//
//    /**
//     * Enables sending a unique application identifier to the RavenDB Server that is used for Client API usage tracking.
//     * It allows RavenDB Server to issue performance hint notifications e.g. during robust topology update requests which could indicate Client API misuse impacting the overall performance
//     * @param sendApplicationIdentifier if option should be enabled
//     */
//    public void setSendApplicationIdentifier(boolean sendApplicationIdentifier) {
//        assertNotFrozen();
//        _sendApplicationIdentifier = sendApplicationIdentifier;
//    }
//
    /**
     * Get the timeout for the second broadcast attempt.
     * Default: 30 seconds
     *
     * Upon failure of the first attempt the request executor will resend the command to all nodes simultaneously.
     * @return ?Duration broadcast timeout
     */
    public function getSecondBroadcastAttemptTimeout(): ?Duration {
        return $this->secondBroadcastAttemptTimeout;
    }

    /**
     * Set the timeout for the second broadcast attempt.
     * Default: 30 seconds
     *
     * Upon failure of the first attempt the request executor will resend the command to all nodes simultaneously.
     * @param ?Duration $secondBroadcastAttemptTimeout broadcast timeout
     *
     * @throws IllegalStateException
     */
    public function setSecondBroadcastAttemptTimeout(?Duration $secondBroadcastAttemptTimeout): void {
        $this->assertNotFrozen();
        $this->secondBroadcastAttemptTimeout = $secondBroadcastAttemptTimeout;
    }

    /**
     * Get the timeout for the first broadcast attempt.
     * Default: 5 seconds
     *
     * First attempt will send a single request to a selected node.
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
     * @param ?Duration $firstBroadcastAttemptTimeout broadcast timeout
     *
     * @throws IllegalStateException
     */
    public function setFirstBroadcastAttemptTimeout(?Duration $firstBroadcastAttemptTimeout): void
    {
        $this->assertNotFrozen();
        $this->firstBroadcastAttemptTimeout = $firstBroadcastAttemptTimeout;
    }

//    /**
//     * Get the wait for indexes after save changes timeout
//     * Default: 15 seconds
//     * @return wait timeout
//     */
//    public Duration getWaitForIndexesAfterSaveChangesTimeout() {
//        return _waitForIndexesAfterSaveChangesTimeout;
//    }
//
//    /**
//     * Set the wait for indexes after save changes timeout
//     * Default: 15 seconds
//     * @param waitForIndexesAfterSaveChangesTimeout wait timeout
//     */
//    public void setWaitForIndexesAfterSaveChangesTimeout(Duration waitForIndexesAfterSaveChangesTimeout) {
//        assertNotFrozen();
//        _waitForIndexesAfterSaveChangesTimeout = waitForIndexesAfterSaveChangesTimeout;
//    }

    /**
     * Get the default timeout for DocumentSession waitForNonStaleResults methods.
     * Default: 15 seconds
     * @return Duration wait timeout
     */
    public function getWaitForNonStaleResultsTimeout(): Duration
    {
        return $this->waitForNonStaleResultsTimeout;
    }

//    /**
//     * Sets the default timeout for DocumentSession waitForNonStaleResults methods.
//     * @param waitForNonStaleResultsTimeout wait timeout
//     */
//    public void setWaitForNonStaleResultsTimeout(Duration waitForNonStaleResultsTimeout) {
//        assertNotFrozen();
//        _waitForNonStaleResultsTimeout = waitForNonStaleResultsTimeout;
//    }
//
//    /**
//     * Gets the default timeout for DocumentSession.advanced().waitForReplicationAfterSaveChanges method.
//     * @return wait timeout
//     */
//    public Duration getWaitForReplicationAfterSaveChangesTimeout() {
//        return _waitForReplicationAfterSaveChangesTimeout;
//    }
//
//    /**
//     * Sets the default timeout for DocumentSession.advanced().waitForReplicationAfterSaveChanges method.
//     * @param waitForReplicationAfterSaveChangesTimeout wait timeout
//     */
//    public void setWaitForReplicationAfterSaveChangesTimeout(Duration waitForReplicationAfterSaveChangesTimeout) {
//        assertNotFrozen();
//        _waitForReplicationAfterSaveChangesTimeout = waitForReplicationAfterSaveChangesTimeout;
//    }
//
//    public Boolean isUseCompression() {
//        if (_useCompression == null) {
//            return true;
//        }
//        return _useCompression;
//    }
//
//    public void setUseCompression(Boolean useCompression) {
//        assertNotFrozen();
//        _useCompression = useCompression;
//    }

    public function getEntityMapper(): EntityMapper
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
     * @param Closure loadBalancerPerSessionContextSelector selector to use
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
    public function isThrowIfQueryPageSizeIsNotSet(): bool {
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


//    public BiFunction<String, ObjectNode, String> getFindJavaClass() {
//        return _findJavaClass;
//    }
//
//    public void setFindJavaClass(BiFunction<String, ObjectNode, String> _findJavaClass) {
//        assertNotFrozen();
//        this._findJavaClass = _findJavaClass;
//    }
//
//    public Function<Class, String> getFindJavaClassName() {
//        return _findJavaClassName;
//    }
//
//    public void setFindJavaClassName(Function<Class, String> findJavaClassName) {
//        assertNotFrozen();
//        _findJavaClassName = findJavaClassName;
//    }
//
//    public Function<Class, String> getFindCollectionName() {
//        return _findCollectionName;
//    }
//
//    public Function<String, Class> getFindJavaClassByName() {
//        return _findJavaClassByName;
//    }
//
//    public void setFindJavaClassByName(Function<String, Class> findJavaClassByName) {
//        _findJavaClassByName = findJavaClassByName;
//    }
//
//    public void setFindCollectionName(Function<Class, String> findCollectionName) {
//        assertNotFrozen();
//        _findCollectionName = findCollectionName;
//    }
//
//    public Function<String, String> getFindIdentityPropertyNameFromCollectionName() {
//        return _findIdentityPropertyNameFromCollectionName;
//    }
//
//    public void setFindIdentityPropertyNameFromCollectionName(Function<String, String> findIdentityPropertyNameFromCollectionName) {
//        assertNotFrozen();
//        this._findIdentityPropertyNameFromCollectionName = findIdentityPropertyNameFromCollectionName;
//    }
//
//    public BiFunction<String, Object, String> getDocumentIdGenerator() {
//        return _documentIdGenerator;
//    }
//
//    public void setDocumentIdGenerator(BiFunction<String, Object, String> documentIdGenerator) {
//        assertNotFrozen();
//        _documentIdGenerator = documentIdGenerator;
//    }
//
//
//    /**
//     *  Translates the types collection name to the document id prefix
//     *  @return translation function
//     */
//    public Function<String, String> getTransformClassCollectionNameToDocumentIdPrefix() {
//        return _transformClassCollectionNameToDocumentIdPrefix;
//    }
//
//    /**
//     *  Translates the types collection name to the document id prefix
//     *  @param transformClassCollectionNameToDocumentIdPrefix value to set
//     */
//    public void setTransformClassCollectionNameToDocumentIdPrefix(Function<String, String> transformClassCollectionNameToDocumentIdPrefix) {
//        assertNotFrozen();
//        this._transformClassCollectionNameToDocumentIdPrefix = transformClassCollectionNameToDocumentIdPrefix;
//    }
//
//    public Function<PropertyDescriptor, Boolean> getFindIdentityProperty() {
//        return _findIdentityProperty;
//    }
//
//    public void setFindIdentityProperty(Function<PropertyDescriptor, Boolean> findIdentityProperty) {
//        assertNotFrozen();
//        this._findIdentityProperty = findIdentityProperty;
//    }

    public function getShouldIgnoreEntityChanges(): ?ShouldIgnoreEntityChangesInterface
    {
        return $this->shouldIgnoreEntityChanges;
    }

    /**
     * @throws IllegalStateException
     */
    public function setShouldIgnoreEntityChanges(ShouldIgnoreEntityChangesInterface $shouldIgnoreEntityChanges): void
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
    private function defaultGetCollectionName(string $className): string
    {
        $result = null;
        if (array_key_exists($className, $this->cachedDefaultTypeCollectionNames)) {
            $result = $this->cachedDefaultTypeCollectionNames[$className];

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
                ", only concrete classes are supported. Did you forget to customize conventions.findCollectionName?"
            );
        }

        if ($reflectionClass->isAbstract()) {
            throw new IllegalStateException(
              "Cannot find collection name for abstract class " . $className .
              ", only concrete class are supported. Did you forget to customize conventions.findCollectionName?"
            );

        }

        $result = StringUtils::pluralize(ClassUtils::getSimpleClassName($className));

        $this->cachedDefaultTypeCollectionNames[$className] = $result;

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
    public function getCollectionName($entity): ?string
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
            $methodName = $this->findCollectionName;
            $collectionName = $this->$methodName($className);
        }

        if ($collectionName != null) {
            return $collectionName;
        }

        return $this->defaultGetCollectionName($className);
    }

    /**
     * Generates the document id.
     *
     * @param string $databaseName Database name
     * @param Object|null $entity Entity
     * @return string document id
     */
    public function generateDocumentId(string $databaseName, ?object $entity): string
    {
        return '12345';
        // @todo: implement this mehotd
//        Class<? > clazz = entity.getClass();
    //
    //        for (Tuple<Class, BiFunction<String, Object, String>> listOfRegisteredIdConvention : _listOfRegisteredIdConventions) {
    //            if (listOfRegisteredIdConvention.first.isAssignableFrom(clazz)) {
    //                return listOfRegisteredIdConvention.second.apply(databaseName, entity);
    //            }
    //        }
    //
    //        return _documentIdGenerator.apply(databaseName, entity);
    }

    //    /**
    //     * Register an id convention for a single type (and all of its derived types.
    //     * Note that you can still fall back to the DocumentIdGenerator if you want.
    //     * @param <TEntity> Entity class
        // * @param clazz Class
        // * @param function Function to use
        // * @return document conventions
        // */
        // @SuppressWarnings("unchecked")
        // public <TEntity> DocumentConventions registerIdConvention(Class<TEntity> clazz, BiFunction<String, TEntity, String> function) {
                // assertNotFrozen();
                //
                // _listOfRegisteredIdConventions.stream()
                // .filter(x -> x.first.equals(clazz))
                // .findFirst()
                // .ifPresent(x -> _listOfRegisteredIdConventions.remove(x));
                //
                // int index;
                // for (index = 0; index < _listOfRegisteredIdConventions.size(); index++) {
                // Tuple<Class, BiFunction<String, Object, String>> entry = _listOfRegisteredIdConventions.get(index);
                // if (entry.first.isAssignableFrom(clazz)) {
                // break;
                // }
                // }
                //
                // _listOfRegisteredIdConventions.add(index, Tuple.create(clazz, (BiFunction<String, Object, String>) function));
                //
                // return this;
                // }
                //
                // /**
                // * Get the java class (if exists) from the document
                // * @param id document id
                // * @param document document to get java class from
                // * @return java class
                // */
                // public String getJavaClass(String id, ObjectNode document) {
                // return _findJavaClass.apply(id, document);
                // }
                //
                // /**
                // * Get the class instance by it's name
                // * @param name class name
                // * @return java class
                // */
                // public Class getJavaClassByName(String name) {
                // return _findJavaClassByName.apply(name);
                // }
                //
                // /**
                // * Get the Java class name to be stored in the entity metadata
                // * @param entityType Entity type
                // * @return java class name
                // */
                // public String getJavaClassName(Class entityType) {
                // return _findJavaClassName.apply(entityType);
                // }

    /**
     * EXPERT: Disable automatic atomic writes with cluster write transactions. If set to 'true', will only consider explicitly
     * added compare exchange values to validate cluster wide transactions.
     * @return bool disable atomic writes
     */
    public function getDisableAtomicDocumentWritesInClusterWideTransaction(): bool
    {
        return $this->disableAtomicDocumentWritesInClusterWideTransaction;
    }

    /**
     * EXPERT: Disable automatic atomic writes with cluster write transactions. If set to 'true', will only consider explicitly
     * added compare exchange values to validate cluster wide transactions.
     * @param bool $value disable atomic writes
     */
    public function setDisableAtomicDocumentWritesInClusterWideTransaction(bool $value): void
    {
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
    public function getIdentityProperty(string $className): string
    {

        if (array_key_exists($className, $this->idPropertyCache)) {
            $info = $this->idPropertyCache[$className];
            if ($info !== null) {
                return $info;
            }
        }


        try {
            $idField = '';

            // @todo: Implement this!!!!
            //          Field idField = Arrays.stream(Introspector.getBeanInfo(clazz).getPropertyDescriptors())
            //                    .filter(x -> _findIdentityProperty.apply(x))
            //                    .findFirst()
            //                    .map(x -> getField(clazz, x.getName()))
            //                    .orElse(null);

            $this->idPropertyCache[$className] = $idField;

            return $idField;
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage());
        }
    }


//    public void updateFrom(ClientConfiguration configuration) {
//        if (configuration == null) {
//            return;
//        }
//
//        synchronized (this) {
//            if (configuration.isDisabled() && _originalConfiguration == null) { // nothing to do
//                return;
//            }
//
//            if (configuration.isDisabled() && _originalConfiguration != null) { // need to revert to original values
//                _maxNumberOfRequestsPerSession = ObjectUtils.firstNonNull(_originalConfiguration.getMaxNumberOfRequestsPerSession(), _maxNumberOfRequestsPerSession);
//                _readBalanceBehavior = ObjectUtils.firstNonNull(_originalConfiguration.getReadBalanceBehavior(), _readBalanceBehavior);
//                _identityPartsSeparator = ObjectUtils.firstNonNull(_originalConfiguration.getIdentityPartsSeparator(), _identityPartsSeparator);
//                _loadBalanceBehavior = ObjectUtils.firstNonNull(_originalConfiguration.getLoadBalanceBehavior(), _loadBalanceBehavior);
//                _loadBalancerContextSeed = ObjectUtils.firstNonNull(_originalConfiguration.getLoadBalancerContextSeed(), _loadBalancerContextSeed);
//
//                _originalConfiguration = null;
//                return;
//            }
//
//            if (_originalConfiguration == null) {
//                _originalConfiguration = new ClientConfiguration();
//                _originalConfiguration.setEtag(-1);
//                _originalConfiguration.setMaxNumberOfRequestsPerSession(_maxNumberOfRequestsPerSession);
//                _originalConfiguration.setReadBalanceBehavior(_readBalanceBehavior);
//                _originalConfiguration.setIdentityPartsSeparator(_identityPartsSeparator);
//                _originalConfiguration.setLoadBalanceBehavior(_loadBalanceBehavior);
//                _originalConfiguration.setLoadBalancerContextSeed(_loadBalancerContextSeed);
//            }
//
//            _maxNumberOfRequestsPerSession = ObjectUtils.firstNonNull(
//                    configuration.getMaxNumberOfRequestsPerSession(),
//                    _originalConfiguration.getMaxNumberOfRequestsPerSession(),
//                    _maxNumberOfRequestsPerSession);
//            _readBalanceBehavior = ObjectUtils.firstNonNull(
//                    configuration.getReadBalanceBehavior(),
//                    _originalConfiguration.getReadBalanceBehavior(),
//                    _readBalanceBehavior
//            );
//            _loadBalanceBehavior = ObjectUtils.firstNonNull(
//                    configuration.getLoadBalanceBehavior(),
//                    _originalConfiguration.getLoadBalanceBehavior(),
//                    _loadBalanceBehavior
//            );
//            _loadBalancerContextSeed = ObjectUtils.firstNonNull(
//                    configuration.getLoadBalancerContextSeed(),
//                    _originalConfiguration.getLoadBalancerContextSeed(),
//                    _loadBalancerContextSeed
//            );
//            _identityPartsSeparator = ObjectUtils.firstNonNull(
//                    configuration.getIdentityPartsSeparator(),
//                    _originalConfiguration.getIdentityPartsSeparator(),
//                    _identityPartsSeparator);
//        }
//    }
//
//    public static String defaultTransformCollectionNameToDocumentIdPrefix(String collectionName) {
//        long upperCount = collectionName.chars()
//                .filter(x -> Character.isUpperCase(x))
//                .count();
//
//
//        if (upperCount <= 1) {
//            return collectionName.toLowerCase();
//        }
//
//        // multiple capital letters, so probably something that we want to preserve caps on.
//        return collectionName;
//    }
//
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
