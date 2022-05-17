<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Operations\GetIndexNamesResponse;
use RavenDB\Documents\Operations\StringResultResponse;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Type\StringArrayResult;

class GetIndexNamesCommand extends RavenCommand
{
    private ?int $start;
    private ?int $pageSize;

    public function __construct(?int $start, int $pageSize)
    {
        parent::__construct(StringArrayResult::class);

        $this->start = $start;
        $this->pageSize = $pageSize;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase()
                    . "/indexes?start=" . $this->start . "&pageSize=" . $this->pageSize . "&namesOnly=true";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $indexNamesResponse = $this->getMapper()->deserialize($response, GetIndexNamesResponse::class, 'json');
        $this->result = $indexNamesResponse->getResults();
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
