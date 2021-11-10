<?php

namespace RavenDB\Documents\Commands\Batches;

class IndexBatchOptions
{
    private bool $waitForIndexes;
    private float $waitForIndexesTimeout;
    private bool $throwOnTimeoutInWaitForIndexes;
    private array $waitForSpecificIndexes;

    public function isWaitForIndexes(): bool
    {
        return $this->waitForIndexes;
    }

    public function setWaitForIndexes(bool $waitForIndexes): void
    {
        $this->waitForIndexes = $waitForIndexes;
    }

    public function getWaitForIndexesTimeout(): float
    {
        return $this->waitForIndexesTimeout;
    }

    public function setWaitForIndexesTimeout(float $waitForIndexesTimeout): void
    {
        $this->waitForIndexesTimeout = $waitForIndexesTimeout;
    }

    public function isThrowOnTimeoutInWaitForIndexes(): bool
    {
        return $this->throwOnTimeoutInWaitForIndexes;
    }

    public function setThrowOnTimeoutInWaitForIndexes(bool $throwOnTimeoutInWaitForIndexes): void
    {
        $this->throwOnTimeoutInWaitForIndexes = $throwOnTimeoutInWaitForIndexes;
    }

    public function getWaitForSpecificIndexes(): array
    {
        return $this->waitForSpecificIndexes;
    }

    public function setWaitForSpecificIndexes(array $waitForSpecificIndexes): void
    {
        $this->waitForSpecificIndexes = $waitForSpecificIndexes;
    }
}
