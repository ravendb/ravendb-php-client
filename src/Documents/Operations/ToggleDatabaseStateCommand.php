<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\ServerNode;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\RavenCommand;
use RavenDB\Utils\RaftIdGenerator;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Exceptions\IllegalArgumentException;

class ToggleDatabaseStateCommand extends RavenCommand
{
    private bool $disable = false;
    private ToggleDatabasesStateParameters $parameters;

    public function __construct(?ToggleDatabasesStateParameters $parameters, bool $disable)
    {
        parent::__construct(DisableDatabaseToggleResult::class);

        if ($parameters == null) {
            throw new IllegalArgumentException("Parameters cannot be null");
        }

        $this->disable    = $disable;
        $this->parameters = $parameters;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $toggle = $this->disable ? 'disable' : 'enable';
        return $serverNode->getUrl() . '/admin/databases/' . $toggle;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->getMapper()->normalize($this->parameters),
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

            $jsonNode = json_decode($response, true);
            $statusNode = $jsonNode["Status"];

            if (!is_array($statusNode)) {
                self::throwInvalidResponse();
            }

            $databaseStatus = $statusNode[0];
            $this->result = $this->getMapper()->denormalize($databaseStatus, $this->getResultClass(), 'json');
        }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
