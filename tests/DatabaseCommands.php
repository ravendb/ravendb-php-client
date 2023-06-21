<?php

namespace tests\RavenDB;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\RequestExecutor;
use RavenDB\Primitives\CleanCloseable;

class DatabaseCommands implements CleanCloseable
{
    private ?DocumentStoreInterface $store = null;
    private ?RequestExecutor $requestExecutor = null;
    private ?InMemoryDocumentSessionOperations $session = null;

    protected function __construct(?DocumentStoreInterface $store, ?string $databaseName = null)
    {
        if ($store == null) {
            throw new IllegalArgumentException('Store cannot be null');
        }

        $this->store = $store;

        /** @var InMemoryDocumentSessionOperations $s */
        $s = $store->openSession($databaseName);
        $this->session = $s;
        $this->requestExecutor = $store->getRequestExecutor($databaseName);
    }

    public function getStore(): ?DocumentStoreInterface
    {
        return $this->store;
    }

    public function getRequestExecutor(): ?RequestExecutor
    {
        return $this->requestExecutor;
    }

    public function getSession(): InMemoryDocumentSessionOperations|null
    {
        return $this->session;
    }

    public static function forStore(?DocumentStoreInterface $store, ?string $databaseName = null): DatabaseCommands
    {
        return new DatabaseCommands($store, $databaseName);
    }

    public function execute(RavenCommand $command): void
    {
        $this->requestExecutor->execute($command);
    }

    public function close(): void
    {
        $this->session->close();
    }
}
