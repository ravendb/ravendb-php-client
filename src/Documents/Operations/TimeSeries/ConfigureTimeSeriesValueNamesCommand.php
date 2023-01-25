<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;

class ConfigureTimeSeriesValueNamesCommand extends RavenCommand implements RaftCommandInterface
{
    private ?ConfigureTimeSeriesValueNamesParameters $parameters = null;

    public function __construct(?ConfigureTimeSeriesValueNamesParameters $parameters)
    {
        parent::__construct(ConfigureTimeSeriesOperationResult::class);
        $this->parameters = $parameters;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/timeseries/names/config";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->getMapper()->normalize($this->parameters),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, $this->resultClass, 'json');
    }
}
