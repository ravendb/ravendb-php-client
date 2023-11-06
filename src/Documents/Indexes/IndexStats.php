<?php

namespace RavenDB\Documents\Indexes;

use DateTimeInterface;

use RavenDB\Http\ResultInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class IndexStats implements ResultInterface
{
    /** @SerializedName ("Name") */
    private ?string $name = null;

    /** @SerializedName ("MapAttempts") */
    private ?int $mapAttempts = null;

    /** @SerializedName ("MapSuccesses") */
    private ?int $mapSuccesses = null;

    /** @SerializedName ("MapErrors") */
    private ?int $mapErrors = null;

    /** @SerializedName ("MapReferenceAttempts") */
    private ?int $mapReferenceAttempts = null;

    /** @SerializedName ("MapReferenceSuccesses") */
    private ?int $mapReferenceSuccesses = null;

    /** @SerializedName ("MapReferenceErrors") */
    private ?int $mapReferenceErrors = null;

    /** @SerializedName ("ReduceAttempts") */
    private ?int $reduceAttempts = null;

    /** @SerializedName ("ReduceSuccesses") */
    private ?int $reduceSuccesses = null;

    /** @SerializedName ("ReduceErrors") */
    private ?int $reduceErrors = null;

    /** @SerializedName ("ReduceOutputCollection") */
    private ?string $reduceOutputCollection = null;

    /** @SerializedName ("ReduceOutputReferencePattern") */
    private ?string $reduceOutputReferencePattern = null;

    /** @SerializedName ("PatternReferencesCollectionName") */
    private ?string $patternReferencesCollectionName = null;

    /** @SerializedName ("MappedPerSecondRate") */
    private ?float $mappedPerSecondRate = null;

    /** @SerializedName ("ReducedPerSecondRate") */
    private ?float $reducedPerSecondRate = null;

    /** @SerializedName ("MaxNumberOfOutputsPerDocument") */
    private ?int $maxNumberOfOutputsPerDocument = null;

    /** @SerializedName ("Collections") */
    private ?CollectionStatsArray $collections = null;

    /** @SerializedName ("LastQueryingTime") */
    private ?DateTimeInterface $lastQueryingTime = null;

    /** @SerializedName ("State") */
    private ?IndexState $state = null;

    /** @SerializedName ("Priority") */
    private ?IndexPriority $priority = null;

    /** @SerializedName ("CreatedTimestamp") */
    private ?DateTimeInterface $createdTimestamp = null;

    /** @SerializedName ("LastIndexingTime") */
    private ?DateTimeInterface $lastIndexingTime = null;

    /** @SerializedName ("Stale") */
    private bool $stale = false;

    /** @SerializedName ("LockMode") */
    private ?IndexLockMode $lockMode = null;

    /** @SerializedName ("Type") */
    private ?IndexType $type = null;

    /** @SerializedName ("Status") */
    private ?IndexRunningStatus $status = null;

    /** @SerializedName ("EntriesCount") */
    private ?int $entriesCount = null;

    /** @SerializedName ("ErrorsCount") */
    private ?int $errorsCount = null;

    /** @SerializedName ("SourceType") */
    private ?IndexSourceType $sourceType = null;

    /** @SerializedName ("IsTestIndex") */
    private bool $isTestIndex = false;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getMapAttempts(): ?int
    {
        return $this->mapAttempts;
    }

    public function setMapAttempts(?int $mapAttempts): void
    {
        $this->mapAttempts = $mapAttempts;
    }

    public function getMapSuccesses(): ?int
    {
        return $this->mapSuccesses;
    }

    public function setMapSuccesses(?int $mapSuccesses): void
    {
        $this->mapSuccesses = $mapSuccesses;
    }

    public function getMapErrors(): ?int
    {
        return $this->mapErrors;
    }

    public function setMapErrors(?int $mapErrors): void
    {
        $this->mapErrors = $mapErrors;
    }

    public function getMapReferenceAttempts(): ?int
    {
        return $this->mapReferenceAttempts;
    }

    public function setMapReferenceAttempts(?int $mapReferenceAttempts): void
    {
        $this->mapReferenceAttempts = $mapReferenceAttempts;
    }

    public function getMapReferenceSuccesses(): ?int
    {
        return $this->mapReferenceSuccesses;
    }

    public function setMapReferenceSuccesses(?int $mapReferenceSuccesses): void
    {
        $this->mapReferenceSuccesses = $mapReferenceSuccesses;
    }

    public function getMapReferenceErrors(): ?int
    {
        return $this->mapReferenceErrors;
    }

    public function setMapReferenceErrors(?int $mapReferenceErrors): void
    {
        $this->mapReferenceErrors = $mapReferenceErrors;
    }

    public function getReduceAttempts(): ?int
    {
        return $this->reduceAttempts;
    }

    public function setReduceAttempts(?int $reduceAttempts): void
    {
        $this->reduceAttempts = $reduceAttempts;
    }

    public function getReduceSuccesses(): ?int
    {
        return $this->reduceSuccesses;
    }

    public function setReduceSuccesses(?int $reduceSuccesses): void
    {
        $this->reduceSuccesses = $reduceSuccesses;
    }

    public function getReduceErrors(): ?int
    {
        return $this->reduceErrors;
    }

    public function setReduceErrors(?int $reduceErrors): void
    {
        $this->reduceErrors = $reduceErrors;
    }

    public function getReduceOutputCollection(): ?string
    {
        return $this->reduceOutputCollection;
    }

    public function setReduceOutputCollection(?string $reduceOutputCollection): void
    {
        $this->reduceOutputCollection = $reduceOutputCollection;
    }

    public function getReduceOutputReferencePattern(): ?string
    {
        return $this->reduceOutputReferencePattern;
    }

    public function setReduceOutputReferencePattern(?string $reduceOutputReferencePattern): void
    {
        $this->reduceOutputReferencePattern = $reduceOutputReferencePattern;
    }

    public function getPatternReferencesCollectionName(): ?string
    {
        return $this->patternReferencesCollectionName;
    }

    public function setPatternReferencesCollectionName(?string $patternReferencesCollectionName): void
    {
        $this->patternReferencesCollectionName = $patternReferencesCollectionName;
    }

    public function getMappedPerSecondRate(): ?float
    {
        return $this->mappedPerSecondRate;
    }

    public function setMappedPerSecondRate(?float $mappedPerSecondRate): void
    {
        $this->mappedPerSecondRate = $mappedPerSecondRate;
    }

    public function getReducedPerSecondRate(): ?float
    {
        return $this->reducedPerSecondRate;
    }

    public function setReducedPerSecondRate(?float $reducedPerSecondRate): void
    {
        $this->reducedPerSecondRate = $reducedPerSecondRate;
    }

    public function getMaxNumberOfOutputsPerDocument(): ?int
    {
        return $this->maxNumberOfOutputsPerDocument;
    }

    public function setMaxNumberOfOutputsPerDocument(?int $maxNumberOfOutputsPerDocument): void
    {
        $this->maxNumberOfOutputsPerDocument = $maxNumberOfOutputsPerDocument;
    }

    public function getCollections(): ?CollectionStatsArray
    {
        return $this->collections;
    }

    public function setCollections(?CollectionStatsArray $collections): void
    {
        $this->collections = $collections;
    }

    public function getLastQueryingTime(): ?DateTimeInterface
    {
        return $this->lastQueryingTime;
    }

    public function setLastQueryingTime(?DateTimeInterface $lastQueryingTime): void
    {
        $this->lastQueryingTime = $lastQueryingTime;
    }

    public function getState(): ?IndexState
    {
        return $this->state;
    }

    public function setState(?IndexState $state): void
    {
        $this->state = $state;
    }

    public function getPriority(): ?IndexPriority
    {
        return $this->priority;
    }

    public function setPriority(?IndexPriority $priority): void
    {
        $this->priority = $priority;
    }

    public function getCreatedTimestamp(): ?DateTimeInterface
    {
        return $this->createdTimestamp;
    }

    public function setCreatedTimestamp(?DateTimeInterface $createdTimestamp): void
    {
        $this->createdTimestamp = $createdTimestamp;
    }

    public function getLastIndexingTime(): ?DateTimeInterface
    {
        return $this->lastIndexingTime;
    }

    public function setLastIndexingTime(?DateTimeInterface $lastIndexingTime): void
    {
        $this->lastIndexingTime = $lastIndexingTime;
    }

    public function isStale(): bool
    {
        return $this->stale;
    }

    public function setStale(bool $stale): void
    {
        $this->stale = $stale;
    }

    public function getLockMode(): ?IndexLockMode
    {
        return $this->lockMode;
    }

    public function setLockMode(?IndexLockMode $lockMode): void
    {
        $this->lockMode = $lockMode;
    }

    public function getType(): ?IndexType
    {
        return $this->type;
    }

    public function setType(?IndexType $type): void
    {
        $this->type = $type;
    }

    public function getStatus(): ?IndexRunningStatus
    {
        return $this->status;
    }

    public function setStatus(?IndexRunningStatus $status): void
    {
        $this->status = $status;
    }

    public function getEntriesCount(): ?int
    {
        return $this->entriesCount;
    }

    public function setEntriesCount(?int $entriesCount): void
    {
        $this->entriesCount = $entriesCount;
    }

    public function getErrorsCount(): ?int
    {
        return $this->errorsCount;
    }

    public function setErrorsCount(?int $errorsCount): void
    {
        $this->errorsCount = $errorsCount;
    }

    public function getSourceType(): ?IndexSourceType
    {
        return $this->sourceType;
    }

    public function setSourceType(?IndexSourceType $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    public function isTestIndex(): bool
    {
        return $this->isTestIndex;
    }

    public function setTestIndex(bool $testIndex): void
    {
        $this->isTestIndex = $testIndex;
    }
}
