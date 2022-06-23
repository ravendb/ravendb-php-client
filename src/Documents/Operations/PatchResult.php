<?php

namespace RavenDB\Documents\Operations;

use DateTimeInterface;
use RavenDB\Http\ResultInterface;

class PatchResult implements ResultInterface
{
    private ?PatchStatus $status = null;
    private ?array $modifiedDocument = null;
    private ?array $originalDocument = null;
    private ?array $debug = null;

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

    public function getOriginalDocument(): ?array
    {
        return $this->originalDocument;
    }

    public function setOriginalDocument(?array $originalDocument): void
    {
        $this->originalDocument = $originalDocument;
    }

    public function getDebug(): ?array
    {
        return $this->debug;
    }

    public function setDebug(?array $debug): void
    {
        $this->debug = $debug;
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
