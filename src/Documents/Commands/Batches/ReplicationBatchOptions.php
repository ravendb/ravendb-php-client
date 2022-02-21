<?php

namespace RavenDB\Documents\Commands\Batches;

class ReplicationBatchOptions
{
    private bool $waitForReplicas = false;
    private int $numberOfReplicasToWaitFor = 0;
    private \DateInterval $waitForReplicasTimeout;
    private bool $majority = false;
    private bool $throwOnTimeoutInWaitForReplicas = true;

    public function __construct()
    {
        $this->waitForReplicasTimeout = new \DateInterval("P0");
    }

    public function isWaitForReplicas(): bool
    {
        return $this->waitForReplicas;
    }

    public function setWaitForReplicas(bool $waitForReplicas): void
    {
        $this->waitForReplicas = $waitForReplicas;
    }

    public function getNumberOfReplicasToWaitFor(): int
    {
        return $this->numberOfReplicasToWaitFor;
    }

    public function setNumberOfReplicasToWaitFor(int $numberOfReplicasToWaitFor): void
    {
        $this->numberOfReplicasToWaitFor = $numberOfReplicasToWaitFor;
    }

    public function getWaitForReplicasTimeout(): \DateInterval
    {
        return $this->waitForReplicasTimeout;
    }

    public function setWaitForReplicasTimeout(\DateInterval $waitForReplicasTimeout): void
    {
        $this->waitForReplicasTimeout = $waitForReplicasTimeout;
    }

    public function isMajority(): bool
    {
        return $this->majority;
    }

    public function setMajority(bool $majority): void
    {
        $this->majority = $majority;
    }

    public function isThrowOnTimeoutInWaitForReplicas(): bool
    {
        return $this->throwOnTimeoutInWaitForReplicas;
    }

    public function setThrowOnTimeoutInWaitForReplicas(bool $throwOnTimeoutInWaitForReplicas): void
    {
        $this->throwOnTimeoutInWaitForReplicas = $throwOnTimeoutInWaitForReplicas;
    }
}
