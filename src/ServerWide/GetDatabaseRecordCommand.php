<?php

namespace RavenDB\ServerWide;

use RavenDB\Http\ServerNode;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\HttpRequestInterface;

class GetDatabaseRecordCommand extends RavenCommand
{
    private ?string $database = null;

    public function __construct(?string $database)
    {
        parent::__construct(DatabaseRecordWithEtag::class);

        $this->database = $database;
    }


    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/admin/databases?name=" . $this->database;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->result = null;
            return;
        }

        $this->result = $this->getMapper()->deserialize($response, $this->getResultClass(), 'json');
    }
}
