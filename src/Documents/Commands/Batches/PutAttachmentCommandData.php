<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;

class PutAttachmentCommandData implements CommandDataInterface
{
    private ?string $id = null;
    private ?string $name = null;
    private $stream = null;
    private ?string $changeVector = null;
    private ?string $contentType = null;
    private ?CommandType $type = null;

    public function __construct(?string $documentId, ?string $name, $stream, ?string $contentType, ?string $changeVector)
    {
        $this->type = CommandType::attachmentPut();

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

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getStream()
    {
        return $this->stream;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getType(): ?CommandType
    {
        return $this->type;
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];
        $data["Id"] = $this->id;
        $data["Name"] = $this->name;
        $data["ContentType"] = $this->contentType;
        $data["ChangeVector"] = $this->changeVector;
        $data["Type"] = $this->type->__toString();
        return $data;
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // left empty on purpose
    }
}
