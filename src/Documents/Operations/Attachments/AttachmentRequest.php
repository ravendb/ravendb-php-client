<?php

namespace RavenDB\Documents\Operations\Attachments;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringUtils;

class AttachmentRequest
{
    private ?string $name = null;
    private ?string $documentId = null;

    public function __construct(?string $documentId, ?string $name)
    {
        if (StringUtils::isBlank($documentId)) {
            throw new IllegalArgumentException("DocumentId cannot be null or whitespace.");
        }
        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("Name cannot be null or whitespace.");
        }

        $this->documentId = $documentId;
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }
}
