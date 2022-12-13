<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;

class TimeSeriesBatchCommand extends VoidRavenCommand
{
    private ?string $documentId = null;
    private ?TimeSeriesOperation $operation = null;
    private ?DocumentConventions $conventions = null;

    public function __construct(?string $documentId, ?TimeSeriesOperation $operation, ?DocumentConventions $conventions)
    {
        parent::__construct();
        $this->documentId = $documentId;
        $this->operation = $operation;
        $this->conventions = $conventions;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/timeseries?docId=" . urlEncode($this->documentId);

    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->operation->serialize($this->conventions),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST, $options);
    }
}
