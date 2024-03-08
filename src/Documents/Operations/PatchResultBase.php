<?php

namespace RavenDB\Documents\Operations;

use DateTimeInterface;

class PatchResultBase
{
    private ?PatchStatus $status = null;
    private ?array $modifiedDocument = null;

    private ?DateTimeInterface $lastModified = null;

    private ?string $changeVector = null;
    private ?string $collection = null;

    public function getStatus(): ?PatchStatus
    {
        return $this->status;
    }

    public function setStatus(?PatchStatus $status): void
    {
        $this->status = $status;
    }

    public function getModifiedDocument(): ?array
    {
        return $this->modifiedDocument;
    }

    public function setModifiedDocument(?array $modifiedDocument): void
    {
        $this->modifiedDocument = $modifiedDocument;
    }

    public function getLastModified(): ?DateTimeInterface
    {
        return $this->lastModified;
    }

    public function setLastModified(?DateTimeInterface $lastModified): void
    {
        $this->lastModified = $lastModified;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function setChangeVector(?string $changeVector): void
    {
        $this->changeVector = $changeVector;
    }

    public function getCollection(): ?string
    {
        return $this->collection;
    }

    public function setCollection(?string $collection): void
    {
        $this->collection = $collection;
    }
}
