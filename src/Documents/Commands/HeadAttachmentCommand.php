<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Constants\Headers;
use RavenDB\Constants\HttpStatusCode;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Extensions\HttpExtensions;
use RavenDB\Http\HttpCache;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\HttpResponse;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ResponseDisposeHandling;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\StringUtils;
use RavenDB\Utils\UrlUtils;

class HeadAttachmentCommand extends RavenCommand
{
    private ?string $documentId = null;
    private ?string $name = null;
    private ?string $changeVector = null;

    public function __construct(?string $documentId, ?string $name, ?string $changeVector = null)
    {
        parent::__construct();

        if (StringUtils::isBlank($documentId)) {
            throw new IllegalArgumentException("DocumentId cannot be null or empty");
        }
        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("Name cannot be null or empty");
        }

        $this->documentId = $documentId;
        $this->name = $name;
        $this->changeVector = $changeVector;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl()
            . "/databases/" . $serverNode->getDatabase()
            . "/attachments?id=" . UrlUtils::escapeDataString($this->documentId)
            . "&name=" . UrlUtils::escapeDataString($this->name);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $httpHead = new HttpRequest($this->createUrl($serverNode), HttpRequest::HEAD);

        if ($this->changeVector !== null) {
            $httpHead->addHeader(Headers::IF_NONE_MATCH, "\"" . $this->changeVector . "\"");
        }

        return $httpHead;
    }

    public function processResponse(?HttpCache $cache, ?HttpResponse $response, string $url): ResponseDisposeHandling
    {
        if ($response->getStatusCode() == HttpStatusCode::NOT_MODIFIED) {
            $this->result = $this->changeVector;
            return ResponseDisposeHandling::automatic();
        }

        if ($response->getStatusCode() == HttpStatusCode::NOT_FOUND) {
            $this->result = null;
            return ResponseDisposeHandling::automatic();
        }

        $this->result = HttpExtensions::getRequiredEtagHeader($response);
        return ResponseDisposeHandling::automatic();
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response != null) {
            $this->throwInvalidResponse();
        }

        $this->result = null;
    }
}
