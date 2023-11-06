<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetNextOperationIdCommand extends RavenCommand
{
    public ?string $nodeTag = null;

    public function __construct()
    {
        parent::__construct(null);
    }

    public function getNodeTag(): ?string
    {
        return $this->nodeTag;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/operations/next-operation-id";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        $jsonNode = json_decode($response, true);

        if (array_key_exists("Id", $jsonNode)) {
            $this->result = intval($jsonNode["Id"]);
        }

        if (array_key_exists("NodeTag", $jsonNode)) {
            $this->nodeTag = strval($jsonNode["NodeTag"]);
        }
    }
}
