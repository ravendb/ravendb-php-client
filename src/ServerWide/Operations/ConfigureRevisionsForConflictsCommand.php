<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\Revisions\RevisionsCollectionConfiguration;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;

class ConfigureRevisionsForConflictsCommand extends RavenCommand
{
    private ?DocumentConventions $conventions = null;
    private ?string $databaseName = null;
    private ?RevisionsCollectionConfiguration $configuration = null;

    public function __construct(?DocumentConventions $conventions, ?string $databaseName, ?RevisionsCollectionConfiguration $configuration)
    {
        parent::__construct(ConfigureRevisionsForConflictsResult::class);


        $this->conventions = $conventions;

        if ($databaseName == null) {
            throw new IllegalArgumentException("Database cannot be null");
        }
        $this->databaseName = $databaseName;
        $this->configuration = $configuration;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $this->databaseName . "/admin/revisions/conflicts/config";
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
        $this->result = $this->getMapper()->deserialize($response, ConfigureRevisionsForConflictsResult::class, 'json');
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
