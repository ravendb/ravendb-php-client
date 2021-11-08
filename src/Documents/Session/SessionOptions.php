<?php

namespace RavenDB\Documents\Session;

use RavenDB\Http\RequestExecutor;

class SessionOptions
{
    private ?string $database = null;
    private bool $noTracking = false; // true; // todo: [Marcin] check default value for this
    private bool $noCaching = false; // true; // todo: [Marcin] check default value for this
    private ?RequestExecutor $requestExecutor = null;
    private TransactionMode $transactionMode;
    private bool $disableAtomicDocumentWritesInClusterWideTransaction = true; // todo [MARCIN] check default value

    public function __construct()
    {
        $this->transactionMode = TransactionMode::singleNode();
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function setDatabase(?string $database): void
    {
        $this->database = $database;
    }

    public function isNoTracking(): bool
    {
        return $this->noTracking;
    }

    public function setNoTracking(bool $noTracking): void
    {
        $this->noTracking = $noTracking;
    }

    public function isNoCaching(): bool
    {
        return $this->noCaching;
    }

    public function setNoCaching(bool $noCaching): void
    {
        $this->noCaching = $noCaching;
    }

    public function getRequestExecutor(): ?RequestExecutor
    {
        return $this->requestExecutor;
    }

    public function setRequestExecutor(?RequestExecutor $requestExecutor): void
    {
        $this->requestExecutor = $requestExecutor;
    }

    public function getTransactionMode(): TransactionMode
    {
        return $this->transactionMode;
    }

    public function setTransactionMode(TransactionMode $transactionMode): void
    {
        $this->transactionMode = $transactionMode;
    }

    public function isDisableAtomicDocumentWritesInClusterWideTransaction(): bool
    {
        return $this->disableAtomicDocumentWritesInClusterWideTransaction;
    }

    public function setDisableAtomicDocumentWritesInClusterWideTransaction(bool $disableAtomicDocumentWritesInClusterWideTransaction): void
    {
        $this->disableAtomicDocumentWritesInClusterWideTransaction = $disableAtomicDocumentWritesInClusterWideTransaction;
    }

    public function getDisableAtomicDocumentWritesInClusterWideTransaction(): bool
    {
        return $this->disableAtomicDocumentWritesInClusterWideTransaction;
    }
}
