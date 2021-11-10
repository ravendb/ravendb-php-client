<?php

namespace RavenDB\Json;

class BatchCommandResult
{
    private array $result;
    private int $transactionIndex;

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): void
    {
        $this->result = $result;
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
