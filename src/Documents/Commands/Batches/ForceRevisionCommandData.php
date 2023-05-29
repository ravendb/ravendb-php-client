<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;

class ForceRevisionCommandData implements CommandDataInterface
{
    private ?string $id = null;
    private ?string $name = null;
    private ?string $changeVector = null;

    public function __construct(string $id)
    {
        if (empty($id)) {
            throw new IllegalArgumentException('Id cannot be null');
        }
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getChangeVector(): string
    {
        return $this->changeVector;
    }

    public function getType(): CommandType
    {
        return CommandType::forceRevisionCreation();
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        return [
            "Id" => $this->id,
            "Type" => $this->getType()->getValue()
        ];
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // TODO: Implement onBeforeSaveChanges() method.
    }
}
