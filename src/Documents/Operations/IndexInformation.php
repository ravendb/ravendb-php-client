<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Indexes\IndexLockMode;
use RavenDB\Documents\Indexes\IndexPriority;
use RavenDB\Documents\Indexes\IndexSourceType;
use RavenDB\Documents\Indexes\IndexState;
use RavenDB\Documents\Indexes\IndexType;

use Symfony\Component\Serializer\Annotation\SerializedName;

class IndexInformation
{
    /** @SerializedName("Name") */
    private ?string $name = null;

    /** @SerializedName("IsStale") */
    private bool $stale = false;

    /** @SerializedName("State") */
    private ?IndexState $state = null;

    /** @SerializedName("LockMode") */
    private ?IndexLockMode $lockMode = null;

    /** @SerializedName("Priority") */
    private ?IndexPriority $priority = null;

    /** @SerializedName("Type") */
    private ?IndexType $type = null;

    /** @SerializedName("LastIndexingTime") */
    private ?\DateTimeInterface $lastIndexingTime = null;

    /** @SerializedName("SourceType") */
    private ?IndexSourceType $sourceType = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function isStale(): bool
    {
        return $this->stale;
    }

    public function setStale(bool $stale): void
    {
        $this->stale = $stale;
    }

    public function getState(): ?IndexState
    {
        return $this->state;
    }

    public function setState(?IndexState $state): void
    {
        $this->state = $state;
    }

    public function getLockMode(): ?IndexLockMode
    {
        return $this->lockMode;
    }

    public function setLockMode(?IndexLockMode $lockMode): void
    {
        $this->lockMode = $lockMode;
    }

    public function getPriority(): ?IndexPriority
    {
        return $this->priority;
    }

    public function setPriority(?IndexPriority $priority): void
    {
        $this->priority = $priority;
    }

    public function getType(): ?IndexType
    {
        return $this->type;
    }

    public function setType(?IndexType $type): void
    {
        $this->type = $type;
    }

    public function getLastIndexingTime(): ?\DateTimeInterface
    {
        return $this->lastIndexingTime;
    }

    public function setLastIndexingTime(?\DateTimeInterface $lastIndexingTime): void
    {
        $this->lastIndexingTime = $lastIndexingTime;
    }

    public function getSourceType(): ?IndexSourceType
    {
        return $this->sourceType;
    }

    public function setSourceType(?IndexSourceType $sourceType): void
    {
        $this->sourceType = $sourceType;
    }
}
