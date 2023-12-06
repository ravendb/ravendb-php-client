<?php

namespace RavenDB\Documents\Operations\Refresh;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;

class ConfigureRefreshCommand extends RavenCommand
{
    private ?RefreshConfiguration $configuration = null;

    public function __construct(?RefreshConfiguration $configuration)
    {
        parent::__construct(ConfigureRefreshOperationResult::class);
        if ($configuration == null) {
            throw new IllegalArgumentException("Configuration cannot be null");
        }
        $this->configuration = $configuration;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/admin/refresh/config";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->getMapper()->normalize($this->configuration),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            self::throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, ConfigureRefreshOperationResult::class, 'json');
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
