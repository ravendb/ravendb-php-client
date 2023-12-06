<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;

class StartIndexingCommand extends VoidRavenCommand
{
    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/admin/indexes/start";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST);
    }
}
