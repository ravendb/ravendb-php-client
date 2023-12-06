<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringUtils;

class MoveAttachmentCommandData implements CommandDataInterface
{
    private ?string $id = null;
    private ?string $name = null;
    private ?string $destinationId = null;
    private ?string $destinationName = null;
    private ?string $changeVector = null;

    public function __construct(?string $documentId, ?string $name, ?string $destinationDocumentId, ?string $destinationName, ?string $changeVector)
    {
        if (StringUtils::isBlank($documentId)) {
            throw new IllegalArgumentException("DocumentId is required");
        }

        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("Name is required");
        }

        if (StringUtils::isBlank($destinationDocumentId)) {
            throw new IllegalArgumentException("DestinationDocumentId is required");
        }

        if (StringUtils::isBlank($destinationName)) {
            throw new IllegalArgumentException("DestinationName is required");
        }

        $this->id = $documentId;
        $this->name = $name;
        $this->destinationId = $destinationDocumentId;
        $this->destinationName = $destinationName;
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
        return CommandType::attachmentMove();
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];

        $data["Id"] = $this->id;
        $data["Name"] = $this->name;
        $data["DestinationId"] = $this->destinationId;
        $data["DestinationName"] = $this-> destinationName;
        $data["ChangeVector"] = $this-> changeVector;
        $data["Type"] = CommandType::attachmentMove()->__toString();

        return $data;
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // leave empty by default
    }
}
