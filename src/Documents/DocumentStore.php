<?php

namespace RavenDB\Documents;

use Closure;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use RavenDB\Documents\Operations\OperationExecutor;
use RavenDB\Documents\Identity\MultiDatabaseHiLoIdGenerator;
use RavenDB\Documents\Operations\MaintenanceOperationExecutor;
use RavenDB\Documents\Session\DocumentSession;
use RavenDB\Documents\Session\DocumentSessionInterface;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\RequestExecutor;
use RavenDB\Primitives\ClosureArray;
use RavenDB\Primitives\EventArgs;
use RavenDB\Primitives\EventHelper;
use RavenDB\Primitives\ExceptionsUtils;
use RavenDB\Type\Url;
use RavenDB\Type\UrlArray;

// !status: IN PROGRESS
class DocumentStore extends DocumentStoreBase
{
//    private ExecutorService $executorService = Executors::newCachedThreadPool();

//    private final ConcurrentMap<String, Lazy<RequestExecutor>> requestExecutors = new ConcurrentSkipListMap<>(String.CASE_INSENSITIVE_ORDER);
//
    private ?MultiDatabaseHiLoIdGenerator $multiDbHiLo = null;

    private ?MaintenanceOperationExecutor $maintenanceOperationExecutor = null;
    private ?OperationExecutor $operationExecutor = null;

//    private DatabaseSmuggler _smuggler;

    private string $identifier = '';

    /**
     * @param UrlArray|Url|array|string|null $urls
     * @param string|null $database
     */
    public function __construct($urls = null, ?string $database = null)
    {
        parent::__construct();

        if ($urls !== null) {
            $this->setUrls($urls);
        }
        $this->setDatabase($database);

        $this->beforeClose = new ClosureArray();
        $this->afterClose = new ClosureArray();
    }

//    public function getExecutorService(): ExecutorService
//    {
//        return $this->executorService;
//    }

    /**
     * Gets the identifier for this store.
     */
    public function getIdentifier(): ?string
    {
        if (!empty($this->identifier)) {
            return $this->identifier;
        }

        if ($this->urls->isEmpty()) {
            return null;
        }

        if ($this->database != null) {
            return join(",", $this->urls->getArrayCopy()) . " (DB: " . $this->database . ")";
        }

        return join(",", $this->urls->getArrayCopy());
    }

    /**
     * Sets the identifier for this store.
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function close(): void
    {
        EventHelper::invoke($this->beforeClose, $this, EventArgs::$EMPTY);
//        EventHelper.invoke(beforeClose, this, EventArgs.EMPTY);
//
//        for (Lazy<EvictItemsFromCacheBasedOnChanges> value : _aggressiveCacheChanges.values()) {
//            if (!value.isValueCreated()) {
//                continue;
//            }
//
//            value.getValue().close();
//        }
//
//        if (_multiDbHiLo != null) {
//            try {
//                _multiDbHiLo.returnUnusedRange();
//            } catch (Exception e) {
//                // ignore
//            }
//        }
//
//        if (subscriptions() != null) {
//            subscriptions().close();
//        }
//
//        disposed = true;
//
        EventHelper::invoke($this->afterClose, $this, EventArgs::$EMPTY);

//        for (Map.Entry<String, Lazy<RequestExecutor>> kvp : requestExecutors.entrySet()) {
//            if (!kvp.getValue().isValueCreated()) {
//                continue;
//            }
//
//            kvp.getValue().getValue().close();
//        }
//
//        executorService.shutdown();
    }

    /**
     * Opens the session for a particular database
     *
     * @param string|SessionOptions $dbNameOrOptions Database to use
     *
     * @return DocumentSessionInterface Document session
     */
    public function openSession($dbNameOrOptions = ''): DocumentSessionInterface
    {
        if (is_string($dbNameOrOptions)) {
            return $this->openSessionWithDatabase($dbNameOrOptions);
        }

        if ($dbNameOrOptions instanceof SessionOptions) {
            return $this->openSessionWithOptions($dbNameOrOptions);
        }

        throw new IllegalArgumentException('Illegal argument provided.');
    }

    /**
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     */
    private function openSessionWithDatabase(string $database = ''): DocumentSessionInterface
    {
        $sessionOptions = new SessionOptions();
        $sessionOptions->setDisableAtomicDocumentWritesInClusterWideTransaction(
            $this->getConventions()->getDisableAtomicDocumentWritesInClusterWideTransaction()
        );
        if ($database) {
            $sessionOptions->setDatabase($database);
        }

        return $this->openSessionWithOptions($sessionOptions);
    }

    /**
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     */
    private function openSessionWithOptions(SessionOptions $sessionOptions): DocumentSessionInterface
    {
        $this->assertInitialized();
        $this->ensureNotClosed();

        $sessionId = Uuid::uuid4();

        $session = new DocumentSession($this, $sessionId, $sessionOptions);

        $this->registerEvents($session);
        $this->afterSessionCreated($session);

        return $session;
    }

    /**
     * @throws InvalidArgumentException
     * @throws IllegalStateException
     */
    public function getRequestExecutor(string $databaseName = null): RequestExecutor
    {
        $this->assertInitialized();
        $databaseName = $this->getEffectiveDatabase($databaseName);

        return RequestExecutor::create($this->getUrls(), $databaseName, $this->authOptions, $this->getConventions());
    }

//    @Override
//    public RequestExecutor getRequestExecutor(String database) {
//        assertInitialized();
//
//        database = getEffectiveDatabase(database);
//
//        Lazy<RequestExecutor> executor = requestExecutors.get(database);
//        if (executor != null) {
//            return executor.getValue();
//        }
//
//        final String effectiveDatabase = database;
//
//        Supplier<RequestExecutor> createRequestExecutor = () -> {
//            RequestExecutor requestExecutor = RequestExecutor.create(getUrls(), effectiveDatabase, getCertificate(), getCertificatePrivateKeyPassword(), getTrustStore(), executorService, getConventions());
//            registerEvents(requestExecutor);
//
//            return requestExecutor;
//        };
//
//        Supplier<RequestExecutor> createRequestExecutorForSingleNode = () -> {
//            RequestExecutor forSingleNode = RequestExecutor.createForSingleNodeWithConfigurationUpdates(getUrls()[0], effectiveDatabase, getCertificate(), getCertificatePrivateKeyPassword(), getTrustStore(), executorService, getConventions());
//            registerEvents(forSingleNode);
//
//            return forSingleNode;
//        };
//
//        if (!getConventions().isDisableTopologyUpdates()) {
//            executor = new Lazy<>(createRequestExecutor);
//        } else {
//            executor = new Lazy<>(createRequestExecutorForSingleNode);
//        }
//
//        requestExecutors.put(database, executor);
//
//        return executor.getValue();
//    }
//
//    @Override
//    public CleanCloseable setRequestTimeout(Duration timeout) {
//        return setRequestTimeout(timeout, null);
//    }
//
//    @Override
//    public CleanCloseable setRequestTimeout(Duration timeout, String database) {
//        assertInitialized();
//
//        database = this.getEffectiveDatabase(database);
//
//        RequestExecutor requestExecutor = getRequestExecutor(database);
//        Duration oldTimeout = requestExecutor.getDefaultTimeout();
//        requestExecutor.setDefaultTimeout(timeout);
//
//        return () -> requestExecutor.setDefaultTimeout(oldTimeout);
//    }


    /**
     * @throws InvalidArgumentException
     */
    public function initialize(): DocumentStoreInterface
    {
        if ($this->initialized) {
            return $this;
        }

        $this->assertValidConfiguration();

//        RequestExecutor.validateUrls(urls, getCertificate());

        try {
            if ($this->getConventions()->getDocumentIdGenerator() == null) { // don't overwrite what the user is doing
                $generator = new MultiDatabaseHiLoIdGenerator($this);
                $this->multiDbHiLo = $generator;

                $this->getConventions()->setDocumentIdGenerator(Closure::fromCallable([$generator, 'generateDocumentId']));
            }

            $this->getConventions()->freeze();
            $this->initialized = true;
        } catch (\Throwable $exception) {
            $this->close();
            throw ExceptionsUtils::unwrapException($exception);
        }

        return $this;
    }


    /**
     * @throws InvalidArgumentException
     */
    private function assertValidConfiguration()
    {
        if ($this->urls == null || $this->urls->isEmpty()) {
            throw new InvalidArgumentException("Document store URLs cannot be empty");
        }
    }




    private ClosureArray $afterClose;
    private ClosureArray $beforeClose;

    public function addBeforeCloseListener(Closure $event): void
    {
        $this->beforeClose->append($event);
    }

    public function removeBeforeCloseListener(Closure $event): void
    {
        if (($key = array_search($event, $this->beforeClose->getArrayCopy())) !== FALSE) {
            $this->beforeClose->offsetUnset($key);
        }
    }

    public function addAfterCloseListener(Closure $event): void
    {
        $this->afterClose->append($event);
    }

    public function removeAfterCloseListener(Closure $event): void
    {
        if (($key = array_search($event, $this->afterClose->getArrayCopy())) !== FALSE) {
            $this->afterClose->offsetUnset($key);
        }
    }

//    @Override
//    public DatabaseSmuggler smuggler() {
//        if (_smuggler == null) {
//            _smuggler = new DatabaseSmuggler(this);
//        }
//
//        return _smuggler;
//    }

    /**
     * @throws IllegalStateException
     */
    public function maintenance(): MaintenanceOperationExecutor
    {
        $this->assertInitialized();

        if ($this->maintenanceOperationExecutor == null) {
            $this->maintenanceOperationExecutor = new MaintenanceOperationExecutor($this);
        }

        return $this->maintenanceOperationExecutor;
    }

    public function operations(): OperationExecutor
    {
        if ($this->operationExecutor == null) {
            $this->operationExecutor = new OperationExecutor($this);
        }

        return $this->operationExecutor;
    }

//    public function bulkInsert(string $database = ''): BulkInsertOperation
//    {
//        $this->assertInitialized();
//
//        return new BulkInsertOperation($this->getEffectiveDatabase($database), $this);
//    }


}
