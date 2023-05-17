<?php

namespace RavenDB\Documents\Session\Operations;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetRevisionsCountCommand extends RavenCommand
{
    private ?string $id = null;

    public function __construct(?string $id)
    {
        parent::__construct(null);

        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        $this->id = $id;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() .
            "/databases/" .
            $serverNode->getDatabase() .
            "/revisions/count?" .
            "&id=" .
            urlEncode($this->id);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->result = 0;
            return;
        }

        $this->result = $this->getMapper()->deserialize($response, DocumentRevisionsCount::class, 'json')->getRevisionsCount();
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
