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
use RavenDB\Type\StringResult;
use RavenDB\Utils\UrlUtils;

class HeadDocumentCommand extends RavenCommand
{
    private ?string $id = null;
    private ?string $changeVector = null;

    public function __construct(?string $idOrCopy, ?string $changeVector)
    {
        parent::__construct(StringResult::class);

        if ($idOrCopy == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        $this->id = $idOrCopy;
        $this->changeVector = $changeVector;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/docs?id=" . UrlUtils::escapeDataString($this->id);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $request =  new HttpRequest($this->createUrl($serverNode), HttpRequest::HEAD);

        if ($this->changeVector != null) {
            $request->addHeader(Headers::IF_NONE_MATCH, $this->changeVector);
        }

        return $request;
    }

    public function processResponse(?HttpCache $cache, ?HttpResponse $response, string $url): ResponseDisposeHandling
    {
        if ($response->getStatusCode() == HttpStatusCode::NOT_MODIFIED) {
            $this->result = new StringResult($this->changeVector);
            return ResponseDisposeHandling::automatic();
        }

        if ($response->getStatusCode() == HttpStatusCode::NOT_FOUND) {
            $this->result = null;
            return ResponseDisposeHandling::automatic();
        }

        $this->result = new StringResult(HttpExtensions::getRequiredEtagHeader($response));
        return ResponseDisposeHandling::automatic();
    }

    public function setResponse(?string $response, bool $fromCache = false): void
    {
        if ($response != null) {
            $this->throwInvalidResponse();
        }
        $this->result = null;
    }
}
