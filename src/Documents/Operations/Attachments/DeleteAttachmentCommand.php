<?php

namespace RavenDB\Documents\Operations\Attachments;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\StringUtils;
use RavenDB\Utils\UrlUtils;

class DeleteAttachmentCommand extends VoidRavenCommand
{
    private ?string $documentId = null;
    private ?string $name = null;
    private ?string $changeVector = null;

    public function __construct(?string $documentId, ?string $name, ?string $changeVector = null)
    {
        parent::__construct();
        if (StringUtils::isBlank($documentId)) {
            throw new IllegalArgumentException("documentId cannot be null");
        }

        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("name cannot be null");
        }

        $this->documentId = $documentId;
        $this->name = $name;
        $this->changeVector = $changeVector;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return
            $serverNode->getUrl() .
            "/databases/" .
            $serverNode->getDatabase() .
            "/attachments?id=" .
            UrlUtils::escapeDataString($this->documentId) .
            "&name=" .
            UrlUtils::escapeDataString($this->name);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $request = new HttpRequest($this->createUrl($serverNode), HttpRequest::DELETE);

        $this->addChangeVectorIfNotNull($this->changeVector, $request);

        return $request;
    }
}
