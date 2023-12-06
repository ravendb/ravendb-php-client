<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\ServerNode;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\HttpRequestInterface;

class DetailedDatabaseStatisticsCommand extends RavenCommand
{
    private ?string $debugTag = null;

    public function __construct(?string $debugTag = null)
    {
        parent::__construct(DetailedDatabaseStatistics::class);
        $this->debugTag = $debugTag;
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . '/databases/' . $serverNode->getDatabase() . '/stats/detailed';

        if ($this->debugTag != null) {
            $url .= '?' . $this->debugTag;
        }

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    /**
     * @throws \ReflectionException
     * @throws \RavenDB\Exceptions\InvalidResultAssignedToCommandException
     */
    public function setResponse(?string $response, bool $fromCache): void
    {
        $result = $this->getMapper()->deserialize($response, DetailedDatabaseStatistics::class, 'json');
        $this->setResult($result);
    }
}
