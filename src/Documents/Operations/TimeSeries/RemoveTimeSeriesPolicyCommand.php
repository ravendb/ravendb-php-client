<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;

class RemoveTimeSeriesPolicyCommand extends RavenCommand implements RaftCommandInterface
{
    private ?string $collection = null;
    private ?string $name = null;

    public function __construct(?string $collection, ?string $name)
    {
        parent::__construct(ConfigureTimeSeriesOperationResult::class);

        $this->collection = $collection;
        $this->name = $name;
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
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase()
            . "/admin/timeseries/policy?collection=" . urlEncode($this->collection) . "&name=" . urlEncode($this->name);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::DELETE);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response,$this->resultClass, 'json');
    }
}
