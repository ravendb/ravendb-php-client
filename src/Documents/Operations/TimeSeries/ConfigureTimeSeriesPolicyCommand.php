<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;

class ConfigureTimeSeriesPolicyCommand extends RavenCommand implements RaftCommandInterface
{
    private ?string $collection = null;
    private ?TimeSeriesPolicy $configuration = null;

    public function __construct(?string $collection, ?TimeSeriesPolicy $configuration)
    {
        parent::__construct(ConfigureTimeSeriesOperationResult::class);

        if ($configuration == null) {
            throw new IllegalArgumentException("Configuration cannot be null");
        }

        if ($collection == null) {
            throw new IllegalArgumentException("Collection cannot be null");
        }

        $this->collection = $collection;
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
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/admin/timeseries/policy?collection=" . urlEncode($this->collection);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->getMapper()->normalize($this->configuration),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, $this->resultClass, 'json');
    }
}
