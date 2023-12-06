<?php

namespace RavenDB\Documents\Operations\Attachments;

class AttachmentDetails extends AttachmentName
{
    private ?string $changeVector = null;
    private ?string $documentId = null;

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function setChangeVector(?string $changeVector): void
    {
        $this->changeVector = $changeVector;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function setDocumentId(?string $documentId): void
    {
        $this->documentId = $documentId;
    }
}
