<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Type\ExtendedArrayObject;
use RavenDB\Utils\StringBuilder;

class GetRevisionsBinEntryCommand extends RavenCommand
{
    private ?int $etag = null;
    private ?int $pageSize = null;

    public function __construct(int $etag, int $pageSize)
    {
        parent::__construct(null);
        $this->etag = $etag;
        $this->pageSize = $pageSize;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $path = new StringBuilder($serverNode->getUrl());
        $path
            ->append("/databases/")
            ->append($serverNode->getDatabase())
            ->append("/revisions/bin?etag=")
            ->append($this->etag);

        if ($this->pageSize != null) {
            $path->append("&pageSize=")
                ->append($this->pageSize);
        }

        return $path->__toString();
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $this->result = json_decode($response, true);
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
