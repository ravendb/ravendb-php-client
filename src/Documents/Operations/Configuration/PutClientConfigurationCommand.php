<?php

namespace RavenDB\Documents\Operations\Configuration;

use RavenDB\Http\ServerNode;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;

class PutClientConfigurationCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private ?ClientConfiguration $configuration = null;

    public function __construct(?DocumentConventions $conventions, ?ClientConfiguration $configuration)
    {
        parent::__construct();

        if ($conventions == null) {
            throw new IllegalArgumentException("conventions cannot be null");
        }

        if ($configuration == null) {
            throw new IllegalArgumentException("configuration cannot be null");
        }

        $this->configuration = $configuration;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . '/databases/' . $serverNode->getDatabase() . '/admin/configuration/client';
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

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
