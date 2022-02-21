<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;

class PutAttachmentCommandData implements CommandDataInterface
{
    private string $id;
    private string $name;
    private string $stream;
    private string $changeVector;
    private string $contentType;
    private CommandType $type;

    public function __construct(string $documentId, string $name, $stream, string $contentType, string $changeVector)
    {
        if (empty($documentId)) {
            throw new IllegalArgumentException('DocumentId cannot be null');
        }
        if (empty($name)) {
            throw new IllegalArgumentException('Name cannot be null');
        }

        $this->id = $documentId;
        $this->name = $name;
        $this->stream = $stream;
        $this->contentType = $contentType;
        $this->changeVector = $changeVector;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStream(): string
    {
        return $this->stream;
    }

    public function getChangeVector(): string
    {
        return $this->changeVector;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getType(): CommandType
    {
        return $this->type;
    }

    public function serialize(DocumentConventions $conventions): void
    {
        // TODO: Implement serialize() method.
    }

    public function onBeforeSaveChanges(InMemoryDocumentSessionOperations $session): void
    {
        // TODO: Implement onBeforeSaveChanges() method.
    }
}
