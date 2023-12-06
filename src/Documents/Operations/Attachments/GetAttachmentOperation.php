<?php

namespace RavenDB\Documents\Operations\Attachments;

use RavenDB\Documents\Attachments\AttachmentType;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;

class GetAttachmentOperation implements OperationInterface
{
    private ?string $documentId = null;
    private ?string $name = null;
    private ?AttachmentType $type = null;
    private ?string $changeVector = null;

    public function __construct(?string $documentId, ?string $name, ?AttachmentType $type, ?string $changeVector)
    {
        $this->documentId = $documentId;
        $this->name = $name;
        $this->type = $type;
        $this->changeVector = $changeVector;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new GetAttachmentCommand($this->documentId, $this->name, $this->type, $this->changeVector);
    }
}
