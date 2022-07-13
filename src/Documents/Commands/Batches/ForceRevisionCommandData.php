<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;

class ForceRevisionCommandData implements CommandDataInterface
{
    private string $id;
    private string $name = "";
    private string $changeVector = "";

    public function __constructor(string $id)
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

    public function serialize(?DocumentConventions $conventions): void
    {
        // TODO: Implement serialize() method.
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // TODO: Implement onBeforeSaveChanges() method.
    }
}
