<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetServerWideOperationStateCommand extends RavenCommand
{
    private int $id;

    public function __construct(int $idOrCopy, ?string $nodeTag = null)
    {
        parent::__construct(null);

        $this->id = $idOrCopy;
        $this->selectedNodeTag = $nodeTag;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . '/operations/state?id=' . $this->id;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            return;
        }
        $node = JsonExtensions::getDefaultMapper()->denormalize($response, \ArrayObject::class);
        $this->result = $node;
    }
}
