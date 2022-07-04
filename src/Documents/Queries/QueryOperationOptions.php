<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Type\Duration;
use RavenDB\Exceptions\IllegalStateException;

class QueryOperationOptions
{
    private ?int $maxOpsPerSecond = null;
    private bool $allowStale = false;
    private ?Duration $staleTimeout = null;
    private bool $retrieveDetails = false;

    /**
     * Limits the amount of base operation per second allowed.
     *
     * @return int|null
     */
    public function getMaxOpsPerSecond(): ?int
    {
        return $this->maxOpsPerSecond;
    }

    /**
     * Limits the amount of base operation per second allowed.
     *
     * @param int|null $maxOpsPerSecond
     */
    public function setMaxOpsPerSecond(?int $maxOpsPerSecond): void
    {
        if ($maxOpsPerSecond != null && $maxOpsPerSecond < 0) {
            throw new IllegalStateException('MaxOpsPerSecond must be greater than 0');
        }
        $this->maxOpsPerSecond = $maxOpsPerSecond;
    }

    public function isAllowStale(): bool
    {
        return $this->allowStale;
    }

    public function setAllowStale(bool $allowStale): void
    {
        $this->allowStale = $allowStale;
    }

    /**
     * If AllowStale is set to false and index is stale, then this is the maximum timeout to wait for index to become non-stale.
     * If timeout is exceeded then exception is thrown.
     *
     * @return Duration|null max time that server can wait for stale results
     */
    public function getStaleTimeout(): ?Duration
    {
        return $this->staleTimeout;
    }

    /**
     * If AllowStale is set to false and index is stale, then this is the maximum timeout to wait for index to become non-stale.
     * If timeout is exceeded then exception is thrown.
     *
     * @param Duration|null $staleTimeout
     */
    public function setStaleTimeout(?Duration $staleTimeout): void
    {
        $this->staleTimeout = $staleTimeout;
    }

    /**
     * Determines whether operation details about each document should be returned by server.
     *
     * @return bool
     */
    public function isRetrieveDetails(): bool
    {
        return $this->retrieveDetails;
    }

    /**
     * Determines whether operation details about each document should be returned by server.
     *
     * @param bool $retrieveDetails
     */
    public function setRetrieveDetails(bool $retrieveDetails): void
    {
        $this->retrieveDetails = $retrieveDetails;
    }
}
