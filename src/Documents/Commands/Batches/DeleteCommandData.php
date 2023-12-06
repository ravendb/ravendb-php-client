<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\BeforeDeleteEventArgs;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;

class DeleteCommandData implements CommandDataInterface
{
    private string $id;
    private string $name = '';
    private ?string $changeVector;
    private CommandType $type;
    private ?string $originalChangeVector;
    private ?array $document = null;

    public function __construct(?string $id, ?string $changeVector = null, ?string $originalChangeVector = null)
    {
        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        $this->id = $id;
        $this->changeVector = $changeVector;
        $this->originalChangeVector = $originalChangeVector;

        $this->type = CommandType::delete();
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

    public function getOriginalChangeVector(): string
    {
        return $this->originalChangeVector;
    }

    public function getType(): CommandType
    {
        return $this->type;
    }

    public function getDocument(): array {
        return $this->document;
    }

    public function setDocument(array $document): void {
        $this->document = $document;
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];
        $data['Id'] = $this->id;
        $data['ChangeVector'] = $this->changeVector;
        $data['Type'] = 'DELETE';
        $data['Document'] = $this->document;

        if ($this->originalChangeVector != null) {
            $data['OriginalChangeVector'] = $this->originalChangeVector;
        }

        $data = array_merge($data, $this->serializeExtraFields());

        return $data;
    }

    public function serializeExtraFields(): array
    {
        // Empty by design
        return [];
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        $session->onBeforeDeleteInvoke(new BeforeDeleteEventArgs($session, $this->id, null));
    }
}
