<?php

namespace RavenDB\Documents\Operations;

class CollectionStatistics
{
    private ?int $countOfDocuments = null;
    private ?int $countOfConflicts = null;
    private array $collections = [];

    public function getCountOfDocuments(): ?int
    {
        return $this->countOfDocuments;
    }

    public function setCountOfDocuments(?int $countOfDocuments): void
    {
        $this->countOfDocuments = $countOfDocuments;
    }

    public function getCountOfConflicts(): ?int
    {
        return $this->countOfConflicts;
    }

    public function setCountOfConflicts(?int $countOfConflicts): void
    {
        $this->countOfConflicts = $countOfConflicts;
    }

    public function getCollections(): array
    {
        return $this->collections;
    }

    public function setCollections(array $collections): void
    {
        $this->collections = $collections;
    }
}
