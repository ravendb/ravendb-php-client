<?php

namespace RavenDB\ServerWide\Operations\OngoingTasks;

use RavenDB\Http\ServerNode;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;

class SetDatabasesLockCommand extends VoidRavenCommand
{
    private array $parameters;

    public function __construct(?DocumentConventions $conventions, ?SetDatabasesLockParameters $parameters)
    {
        if ($conventions == null) {
            throw new IllegalArgumentException('Conventions cannot be null');
        }

        if ($parameters == null) {
            throw new IllegalArgumentException("Parameters cannot be null");
        }

        parent::__construct();

        $this->parameters = $this->getMapper()->normalize($parameters);
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/admin/databases/set-lock";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->parameters,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST, $options);
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
