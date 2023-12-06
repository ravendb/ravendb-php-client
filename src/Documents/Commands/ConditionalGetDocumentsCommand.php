<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Constants\Headers;
use RavenDB\Constants\HttpStatusCode;
use RavenDB\Http\HttpCache;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\HttpResponse;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ResponseDisposeHandling;
use RavenDB\Http\ServerNode;

class ConditionalGetDocumentsCommand extends RavenCommand
{
    private ?string $changeVector = null;
    private ?string $id = null;

    public function __construct(?string $id, ?string $changeVector)
    {
        parent::__construct(ConditionalGetResult::class);

        $this->changeVector = $changeVector;
        $this->id = $id;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/docs?id=" . urlEncode($this->id);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $request =  new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);

        $request->addHeader(Headers::IF_NONE_MATCH, $this->changeVector);

        return $request;
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->result = null;
            return;
        }

        $this->result = $this->getMapper()->deserialize($response, ConditionalGetResult::class, 'json');
    }

    public function processResponse(?HttpCache $cache, ?HttpResponse $response, string $url): ResponseDisposeHandling
    {
        if ($response->getStatusCode() == HttpStatusCode::NOT_MODIFIED) {
            return ResponseDisposeHandling::automatic();
        }

        $result = parent::processResponse($cache, $response, $url);
        $this->result->setChangeVector($response->getFirstHeader("ETag"));
        return $result;
    }
}
