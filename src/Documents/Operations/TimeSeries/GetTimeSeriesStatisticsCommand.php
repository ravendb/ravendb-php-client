<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetTimeSeriesStatisticsCommand extends RavenCommand
{
    private ?string $documentId = null;

    public function __construct(?string $documentId) {
        parent::__construct(TimeSeriesStatistics::class);
        $this->documentId = $documentId;
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/timeseries/stats?docId=" . urlEncode($this->documentId);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        $this->result = $this->getMapper()->deserialize($response, $this->resultClass, 'json');
    }
}
