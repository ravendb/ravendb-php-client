<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;

class ConfigureTimeSeriesCommand extends RavenCommand implements RaftCommandInterface
{
    private ?TimeSeriesConfiguration $configuration = null;

    public function __construct(?TimeSeriesConfiguration $configuration)
    {
        parent::__construct(ConfigureTimeSeriesOperationResult::class);

        $this->configuration = $configuration;
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
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/admin/timeseries/config";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->getMapper()->normalize($this->configuration),
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

        $this->result = $this->getMapper()->deserialize($response, ConfigureTimeSeriesOperationResult::class, 'json');
    }
}
