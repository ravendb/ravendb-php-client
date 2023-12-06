<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringUtils;

class CopyAttachmentCommandData implements CommandDataInterface
{
    private ?string $id = null;
    private ?string $name = null;
    private ?string $destinationId = null;
    private ?string $destinationName = null;
    private ?string $changeVector = null;

    public function __construct(?string $sourceDocumentId, ?string $sourceName, ?string $destinationDocumentId, ?string $destinationName, ?string $changeVector)
    {
        if (StringUtils::isBlank($sourceDocumentId)) {
            throw new IllegalArgumentException("SourceDocumentId is required");
        }

        if (StringUtils::isBlank($sourceName)) {
            throw new IllegalArgumentException("SourceName is required");
        }

        if (StringUtils::isBlank($destinationDocumentId)) {
            throw new IllegalArgumentException("DestinationDocumentId is required");
        }

        if (StringUtils::isBlank($destinationName)) {
            throw new IllegalArgumentException("DestinationName is required");
        }

        $this->id = $sourceDocumentId;
        $this->name = $sourceName;
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
        return CommandType::attachmentCopy();
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];
        $data["Id"] = $this->id;
        $data["Name"] = $this->name;
        $data["DestinationId"] = $this->destinationId;
        $data["DestinationName"] = $this->destinationName;
        $data["ChangeVector"] = $this->changeVector;
        $data["Type"] = CommandType::attachmentCopy()->__toString();

        return $data;
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // leave empty by default
    }
}
