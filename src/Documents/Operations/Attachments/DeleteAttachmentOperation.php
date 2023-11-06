<?php

namespace RavenDB\Documents\Operations\Attachments;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\VoidOperationInterface;
use RavenDB\Http\HttpCache;
use RavenDB\Http\VoidRavenCommand;

class DeleteAttachmentOperation implements VoidOperationInterface
{
    private ?string $documentId = null;
    private ?string $name = null;
    private ?string $changeVector = null;

    public function __construct(?string $documentId, ?string $name, ?string $changeVector = null)
    {
        $this->documentId = $documentId;
        $this->name = $name;
        $this->changeVector = $changeVector;
    }

    function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): VoidRavenCommand
    {
        return new DeleteAttachmentCommand($this->documentId, $this->name, $this->changeVector);
    }
}
