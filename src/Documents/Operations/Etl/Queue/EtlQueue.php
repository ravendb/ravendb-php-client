<?php

namespace RavenDB\Documents\Operations\Etl\Queue;

class EtlQueue
{
    private ?string $name = null;
    private bool $deleteProcessedDocuments = false;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function isDeleteProcessedDocuments(): bool
    {
        return $this->deleteProcessedDocuments;
    }

    public function setDeleteProcessedDocuments(bool $deleteProcessedDocuments): void
    {
        $this->deleteProcessedDocuments = $deleteProcessedDocuments;
    }
}
