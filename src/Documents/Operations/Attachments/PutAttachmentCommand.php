<?php

namespace RavenDB\Documents\Operations\Attachments;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\StringUtils;
use RavenDB\Utils\UrlUtils;

class PutAttachmentCommand extends RavenCommand
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
        parent::__construct(AttachmentDetails::class);

        if (StringUtils::isBlank($documentId)) {
            throw new IllegalArgumentException("documentId cannot be null");
        }

        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("name cannot be null");
        }

        $this->documentId = $documentId;
        $this->name = $name;
        $this->stream = $stream;
        $this->contentType = $contentType;
        $this->changeVector = $changeVector;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url =
            $serverNode->getUrl() .
            "/databases/" .
            $serverNode->getDatabase() .
            "/attachments?id=" .
            UrlUtils::escapeDataString($this->documentId) .
            "&name=" .
            UrlUtils::escapeDataString($this->name);

        if (StringUtils::isNotEmpty($this->contentType)) {
            $url .= "&contentType=" . UrlUtils::escapeDataString($this->contentType);
        }

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'body' => $this->stream
        ];

        $request = new HttpRequest($this->createUrl($serverNode), HttpRequest::POST, $options);

        $this->addChangeVectorIfNotNull($this->changeVector, $request);

        return $request;
    }
}
