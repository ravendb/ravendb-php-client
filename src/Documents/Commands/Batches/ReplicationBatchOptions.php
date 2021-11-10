<?php

namespace RavenDB\Documents\Commands\Batches;

class ReplicationBatchOptions
{
    private bool $waitForReplicas;
    private int $numberOfReplicasToWaitFor;
    private float $waitForReplicasTimeout;
    private bool $majority;
    private bool $throwOnTimeoutInWaitForReplicas = true;

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

    public function getWaitForReplicasTimeout(): float
    {
        return $this->waitForReplicasTimeout;
    }

    public function setWaitForReplicasTimeout(float $waitForReplicasTimeout): void
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
