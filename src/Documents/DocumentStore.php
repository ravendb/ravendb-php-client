<?php

namespace RavenDB\Documents;

use Ramsey\Uuid\Uuid;
use RavenDB\Documents\Operations\MaintenanceOperationExecutor;
use RavenDB\Documents\Session\DocumentSession;
use RavenDB\Documents\Session\DocumentSessionInterface;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\RequestExecutor;

class DocumentStore extends DocumentStoreBase
{

    protected ?MaintenanceOperationExecutor $maintenanceOperationExecutor = null;

    public function __construct(string $database)
    {
        parent::__construct($database);
    }

    /**
     * @throws IllegalArgumentException
     */
    public function initialize(): DocumentStoreInterface
    {
        if ($this->initialized) {
            return $this;
        }

        $this->assertValidConfiguration();

//        RequestExecutor.validateUrls(urls, getCertificate());
//
        try {
//            if (getConventions().getDocumentIdGenerator() == null) { // don't overwrite what the user is doing
//                MultiDatabaseHiLoIdGenerator generator = new MultiDatabaseHiLoIdGenerator(this);
//                _multiDbHiLo = generator;
//
//                getConventions().setDocumentIdGenerator(generator::generateDocumentId);
//            }
//
            $this->getConventions()->freeze();
            $this->initialized = true;
        } catch (\Throwable $exception) {
            $this->close();
//            throw ExceptionsUtils.unwrapException(e);
        }

        return $this;
    }

    public function close(): void
    {
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
//        for (IDatabaseChanges changes : _databaseChanges.values()) {
//            try (CleanCloseable value = changes) {
//                // try will close all values
//            }
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
//        EventHelper.invoke(new ArrayList<>(afterClose), this, EventArgs.EMPTY);
//
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
     * @throws IllegalArgumentException
     * @throws IllegalStateException
     */
    public function getRequestExecutor(string $databaseName = null): RequestExecutor
    {
        $this->assertInitialized();
        $databaseName = $this->getEffectiveDatabase($databaseName);

        return RequestExecutor::create($this->getUrls(), $databaseName, $this->getConventions());
    }

    /**
     * @throws IllegalStateException
     * @throws IllegalArgumentException
     */
    public function openSession(string $database = ''): DocumentSessionInterface
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
     * @throws IllegalArgumentException
     */
    public function openSessionWithOptions(SessionOptions $sessionOptions): DocumentSessionInterface
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
     * @throws IllegalArgumentException
     */
    private function assertValidConfiguration()
    {
        if ($this->urls == null || $this->urls->count() == 0) {
            throw new IllegalArgumentException("Document store URLs cannot be empty");
        }
    }

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
}
