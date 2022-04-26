<?php

namespace RavenDB\Documents\Operations;

use DateTimeInterface;
use RavenDB\Http\ResultInterface;
use RavenDB\Utils\Size;

class DatabaseStatistics implements ResultInterface
{
    private ?int $lastDocEtag = null;
    private ?int $lastDatabaseEtag = null;
    private ?int $countOfIndexes = null;
    private ?int $countOfDocuments = null;
    private ?int $countOfRevisionDocuments = null;
    private ?int $countOfDocumentsConflicts = null;
    private ?int $countOfTombstones = null;
    private ?int $countOfConflicts = null;
    private ?int $countOfAttachments = null;
    private ?int $countOfUniqueAttachments = null;
    private ?int $countOfCounterEntries = null;
    private ?int $countOfTimeSeriesSegments = null;

    private ?IndexInformationArray $indexes = null;

    private ?string $databaseChangeVector = null;
    private ?string $databaseId = null;
    private bool $is64Bit = false;
    private ?string $pager = null;
    private ?DateTimeInterface $lastIndexingTime = null;
    private ?Size $sizeOnDisk = null;
    private ?Size $tempBuffersSizeOnDisk = null;
    private ?int $numberOfTransactionMergerQueueOperations = null;

    public function getStaleIndexes(): IndexInformationArray
    {
        return IndexInformationArray::fromArray(array_map(function (IndexInformation $index) {
            return $index->isStale();
        }, $this->indexes->getArrayCopy()));
    }

    public function getIndexes(): ?IndexInformationArray
    {
        return $this->indexes;
    }

    public function setIndexes(?IndexInformationArray $indexes): void
    {
        $this->indexes = $indexes;
    }

    public function getLastDocEtag(): ?int
    {
        return $this->lastDocEtag;
    }

    public function setLastDocEtag(?int $lastDocEtag): void
    {
        $this->lastDocEtag = $lastDocEtag;
    }

    public function getLastDatabaseEtag(): ?int
    {
        return $this->lastDatabaseEtag;
    }

    public function setLastDatabaseEtag(?int $lastDatabaseEtag): void
    {
        $this->lastDatabaseEtag = $lastDatabaseEtag;
    }

    public function getCountOfIndexes(): ?int
    {
        return $this->countOfIndexes;
    }

    public function setCountOfIndexes(?int $countOfIndexes): void
    {
        $this->countOfIndexes = $countOfIndexes;
    }

    public function getCountOfDocuments(): ?int
    {
        return $this->countOfDocuments;
    }

    public function setCountOfDocuments(?int $countOfDocuments): void
    {
        $this->countOfDocuments = $countOfDocuments;
    }

    public function getCountOfRevisionDocuments(): ?int
    {
        return $this->countOfRevisionDocuments;
    }

    public function setCountOfRevisionDocuments(?int $countOfRevisionDocuments): void
    {
        $this->countOfRevisionDocuments = $countOfRevisionDocuments;
    }

    public function getCountOfDocumentsConflicts(): ?int
    {
        return $this->countOfDocumentsConflicts;
    }

    public function setCountOfDocumentsConflicts(?int $countOfDocumentsConflicts): void
    {
        $this->countOfDocumentsConflicts = $countOfDocumentsConflicts;
    }

    public function getCountOfTombstones(): ?int
    {
        return $this->countOfTombstones;
    }

    public function setCountOfTombstones(?int $countOfTombstones): void
    {
        $this->countOfTombstones = $countOfTombstones;
    }

    public function getCountOfConflicts(): ?int
    {
        return $this->countOfConflicts;
    }

    public function setCountOfConflicts(?int $countOfConflicts): void
    {
        $this->countOfConflicts = $countOfConflicts;
    }

    public function getCountOfAttachments(): ?int
    {
        return $this->countOfAttachments;
    }

    public function setCountOfAttachments(?int $countOfAttachments): void
    {
        $this->countOfAttachments = $countOfAttachments;
    }

    public function getCountOfUniqueAttachments(): ?int
    {
        return $this->countOfUniqueAttachments;
    }

    public function setCountOfUniqueAttachments(?int $countOfUniqueAttachments): void
    {
        $this->countOfUniqueAttachments = $countOfUniqueAttachments;
    }

    public function getCountOfCounterEntries(): ?int
    {
        return $this->countOfCounterEntries;
    }

    public function setCountOfCounterEntries(?int $countOfCounterEntries): void
    {
        $this->countOfCounterEntries = $countOfCounterEntries;
    }

    public function getCountOfTimeSeriesSegments(): ?int
    {
        return $this->countOfTimeSeriesSegments;
    }

    public function setCountOfTimeSeriesSegments(?int $countOfTimeSeriesSegments): void
    {
        $this->countOfTimeSeriesSegments = $countOfTimeSeriesSegments;
    }

    public function getDatabaseChangeVector(): ?string
    {
        return $this->databaseChangeVector;
    }

    public function setDatabaseChangeVector(?string $databaseChangeVector): void
    {
        $this->databaseChangeVector = $databaseChangeVector;
    }

    public function getDatabaseId(): ?string
    {
        return $this->databaseId;
    }

    public function setDatabaseId(?string $databaseId): void
    {
        $this->databaseId = $databaseId;
    }

    public function isIs64Bit(): bool
    {
        return $this->is64Bit;
    }

    public function setIs64Bit(bool $is64Bit): void
    {
        $this->is64Bit = $is64Bit;
    }

    public function getPager(): ?string
    {
        return $this->pager;
    }

    public function setPager(?string $pager): void
    {
        $this->pager = $pager;
    }

    public function getLastIndexingTime(): ?DateTimeInterface
    {
        return $this->lastIndexingTime;
    }

    public function setLastIndexingTime(?DateTimeInterface $lastIndexingTime): void
    {
        $this->lastIndexingTime = $lastIndexingTime;
    }

    public function getSizeOnDisk(): ?Size
    {
        return $this->sizeOnDisk;
    }

    public function setSizeOnDisk(?Size $sizeOnDisk): void
    {
        $this->sizeOnDisk = $sizeOnDisk;
    }

    public function getTempBuffersSizeOnDisk(): ?Size
    {
        return $this->tempBuffersSizeOnDisk;
    }

    public function setTempBuffersSizeOnDisk(?Size $tempBuffersSizeOnDisk): void
    {
        $this->tempBuffersSizeOnDisk = $tempBuffersSizeOnDisk;
    }

    public function getNumberOfTransactionMergerQueueOperations(): ?int
    {
        return $this->numberOfTransactionMergerQueueOperations;
    }

    public function setNumberOfTransactionMergerQueueOperations(?int $numberOfTransactionMergerQueueOperations): void
    {
        $this->numberOfTransactionMergerQueueOperations = $numberOfTransactionMergerQueueOperations;
    }
}
