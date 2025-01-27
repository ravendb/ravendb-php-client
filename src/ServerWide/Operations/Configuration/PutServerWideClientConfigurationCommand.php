<?php

namespace RavenDB\ServerWide\Operations\Configuration;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\Configuration\ClientConfiguration;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;

class PutServerWideClientConfigurationCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private ?ClientConfiguration $configuration = null;

    public function __construct(?DocumentConventions $conventions, ?ClientConfiguration $configuration)
    {
        parent::__construct();

        if ($conventions == null) {
            throw new IllegalArgumentException("Conventions cannot be null");
        }

        if ($configuration == null) {
            throw new IllegalArgumentException("Configuration cannot be null");
        }

        $this->configuration = $configuration;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/admin/configuration/client";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->configuration,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
