<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\PatchRequest;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;

class PatchCommandData implements CommandDataInterface
{
    private string $id;
    private ?string $name = null;
    private ?array $createIfMissing = null;
    private ?string $changeVector = null;
    private ?PatchRequest $patch = null;
    private ?PatchRequest $patchIfMissing = null;
    private bool $returnDocument = false;

    public function __construct(?string $id, ?string $changeVector, ?PatchRequest $patch, ?PatchRequest $patchIfMissing = null)
    {
        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        if ($patch == null) {
            throw new IllegalArgumentException("Patch cannot be null");
        }
        $this->id = $id;
        $this->patch = $patch;
        $this->changeVector = $changeVector;
        $this->patchIfMissing = $patchIfMissing;
    }

    public function getCreateIfMissing(): ?array
    {
        return $this->createIfMissing;
    }

    public function setCreateIfMissing(?array $createIfMissing): void
    {
        $this->createIfMissing = $createIfMissing;
    }

    public function isReturnDocument(): bool
    {
        return $this->returnDocument;
    }

    public function setReturnDocument(bool $returnDocument)
    {
        $this->returnDocument = $returnDocument;
    }

    public function getType(): CommandType
    {
        return CommandType::patch();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function getPatch(): ?PatchRequest
    {
        return $this->patch;
    }

    public function getPatchIfMissing(): ?PatchRequest
    {
        return $this->patchIfMissing;
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];

        $data['Id'] = $this->id;
        $data['ChangeVector'] = $this->changeVector;

        $data['Patch'] = $this->patch->serialize($conventions->getEntityMapper());

        $data['Type'] = "PATCH";

        if ($this->patchIfMissing != null) {
            $data["PatchIfMissing"] = $this->patchIfMissing->serialize($conventions->getEntityMapper());
        }

        if ($this->createIfMissing != null) {
            $data["CreateIfMissing"] = $this->createIfMissing;
        }

        if ($this->returnDocument) {
            $data["ReturnDocument"] = $this->returnDocument;
        }

        return $data;
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        $this->returnDocument = $session->isLoaded($this->getId());
    }
}
