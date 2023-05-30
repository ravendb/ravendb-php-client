<?php

namespace RavenDB\ServerWide\Sorters;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;

class DeleteServerWideSorterCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private ?string $sorterName = null;

    public function __construct(?string $sorterName)
    {
        parent::__construct();

        if ($sorterName == null) {
            throw new IllegalArgumentException("SorterName cannot be null");
        }

        $this->sorterName = $sorterName;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/admin/sorters?name=" . urlEncode($this->sorterName);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::DELETE);
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
