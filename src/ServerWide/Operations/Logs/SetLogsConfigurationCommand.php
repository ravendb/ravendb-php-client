<?php

namespace RavenDB\ServerWide\Operations\Logs;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;

class SetLogsConfigurationCommand extends VoidRavenCommand
{
    private ?SetLogsConfigurationParameters $parameters = null;

    public function __construct(?SetLogsConfigurationParameters $parameters)
    {
        parent::__construct();

        if ($parameters == null) {
            throw new IllegalArgumentException('Parameters cannot be null');
        }

        $this->parameters = $parameters;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/admin/logs/configuration";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'json' => $this->getMapper()->normalize($this->parameters)
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST, $options);
    }
}
