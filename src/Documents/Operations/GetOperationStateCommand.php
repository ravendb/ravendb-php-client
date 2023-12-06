<?php

namespace RavenDB\Documents\Operations;

use ArrayObject;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetOperationStateCommand extends RavenCommand
{
    private int $id;

    public function __construct(int $idOrCopy, string $nodeTag)
    {
        parent::__construct(null);

        $this->id = $idOrCopy;
        $this->selectedNodeTag = $nodeTag;
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/operations/state?id=" . $this->id;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            return;
        }

        $array = json_decode($response, true);

        $this->result = $array;
    }
}
