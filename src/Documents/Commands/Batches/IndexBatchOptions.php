<?php

namespace RavenDB\Documents\Commands\Batches;

class IndexBatchOptions
{
    private bool $waitForIndexes = false;
    private \DateInterval $waitForIndexesTimeout;
    private bool $throwOnTimeoutInWaitForIndexes = false;
    private array $waitForSpecificIndexes = [];

    public function __construct()
    {
        $this->waitForIndexesTimeout = new \DateInterval("P0");
    }

    public function isWaitForIndexes(): bool
    {
        return $this->waitForIndexes;
    }

    public function setWaitForIndexes(bool $waitForIndexes): void
    {
        $this->waitForIndexes = $waitForIndexes;
    }

    public function getWaitForIndexesTimeout(): \DateInterval
    {
        return $this->waitForIndexesTimeout;
    }

    public function setWaitForIndexesTimeout(\DateInterval $waitForIndexesTimeout): void
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
