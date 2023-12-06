<?php

namespace RavenDB\Documents\Indexes;

use DateTimeInterface;

class RollingIndexDeployment
{
    private RollingIndexState $state;
    private ?DateTimeInterface $createdAt = null;
    private ?DateTimeInterface $startedAt = null;
    private ?DateTimeInterface $finishedAt = null;

    public function getState(): RollingIndexState
    {
        return $this->state;
    }

    public function setState(RollingIndexState $state): void
    {
        $this->state = $state;
    }

    public function getCreatedAt(): ?DateTimeInterface {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getStartedAt(): DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTimeInterface $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getFinishedAt(): ?DateTimeInterface {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeInterface $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }
}
