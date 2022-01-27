<?php

namespace RavenDB\Json;

use RavenDB\Http\ResultInterface;

// !status: DONE
class BatchCommandResult implements ResultInterface
{
    private array $results = [];
    private int $transactionIndex = 0;

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function getTransactionIndex(): int
    {
        return $this->transactionIndex;
    }

    public function setTransactionIndex(int $transactionIndex): void
    {
        $this->transactionIndex = $transactionIndex;
    }
}
