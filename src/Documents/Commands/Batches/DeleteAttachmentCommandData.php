<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringUtils;

class DeleteAttachmentCommandData implements CommandDataInterface
{
    private ?string $id = null;
    private ?string $name = null;
    private ?string $changeVector = null;
    private CommandType $type;

    public function __construct(?string $documentId, ?string $name, ?string $changeVector)
    {
        $this->type = CommandType::attachmentDelete();

        if (StringUtils::isBlank($documentId)) {
            throw new IllegalArgumentException("DocumentId cannot be null");
        }

        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("Name cannot be null");
        }

        $this->id = $documentId;
        $this->name = $name;
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

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
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
        $data["ChangeVector"] = $this->changeVector;
        $data["Type"] = $this->type->__toString();

        return $data;
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // leave empty by default
    }
}
