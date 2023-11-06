<?php

namespace RavenDB\Documents\Operations\Attachments;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;

class PutAttachmentOperation implements OperationInterface
{
    private ?string $documentId = null;
    private ?string $name = null;
    private $stream = null;
    private ?string $contentType = null;
    private ?string $changeVector = null;

    /**
     * @param string|null $documentId
     * @param string|null $name
     * @param mixed $stream
     * @param string|null $contentType
     * @param string|null $changeVector
     */
    public function __construct(?string $documentId, ?string $name, $stream, ?string $contentType = null, ?string $changeVector = null)
    {
        $this->documentId = $documentId;
        $this->name = $name;
        $this->stream = $stream;
        $this->contentType = $contentType;
        $this->changeVector = $changeVector;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new PutAttachmentCommand($this->documentId, $this->name, $this->stream, $this->contentType, $this->changeVector);
    }
}
