<?php

namespace RavenDB\Documents\Operations\Counters;

class CounterDetail
{
    private ?string $documentId = null;
    private ?string $counterName = null;
    private ?int $totalValue = null;
    private ?int $etag = null;
    private ?array $counterValues = [];

    private ?string $changeVector = null;

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function setDocumentId(?string $documentId): void
    {
        $this->documentId = $documentId;
    }

    public function getCounterName(): ?string
    {
        return $this->counterName;
    }

    public function setCounterName(?string $counterName): void
    {
        $this->counterName = $counterName;
    }

    public function getTotalValue(): ?int
    {
        return $this->totalValue;
    }

    public function setTotalValue(?int $totalValue): void
    {
        $this->totalValue = $totalValue;
    }

    public function getEtag(): ?int
    {
        return $this->etag;
    }

    public function setEtag(?int $etag): void
    {
        $this->etag = $etag;
    }

    public function getCounterValues(): ?array
    {
        return $this->counterValues;
    }

    public function setCounterValues(?array $counterValues): void
    {
        $this->counterValues = $counterValues;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function setChangeVector(?string $changeVector): void
    {
        $this->changeVector = $changeVector;
    }
}
