<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Indexes\IndexStatsArray;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetIndexesStatisticsCommand extends RavenCommand
{
    public function __construct()
    {
        parent::__construct(IndexStatsArray::class);
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/indexes/stats";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            self::throwInvalidResponse();
        }
        $responseData = json_decode($response, true);

        $resultsData = [];
        if (array_key_exists('Results', $responseData)) {
            $resultsData = $responseData['Results'];
        }

        $this->result = $this->getMapper()->denormalize($resultsData, $this->getResultClass());
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
