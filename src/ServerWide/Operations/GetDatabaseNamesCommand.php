<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Http\GetDatabaseNamesResponse;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetDatabaseNamesCommand extends RavenCommand
{
    private int $start;
    private int $pageSize;

    public function __construct(int $start, int $pageSize)
    {
        $this->start = $start;
        $this->pageSize = $pageSize;

        parent::__construct(GetDatabaseNamesResponse::class);
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() .
            '/databases?start=' . $this->start .
            '&pageSize=' . $this->pageSize .
            '&namesOnly=true';
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    /**
     * @throws \ReflectionException
     * @throws \RavenDB\Exceptions\InvalidResultAssignedToCommandException
     */
    public function setResponse(?string $response, bool $fromCache): void
    {
        $this->setResult($this->getMapper()->deserialize($response, $this->getResultClass(), 'json'));
    }
}
