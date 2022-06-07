<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Operations\IndexHasChangedResponse;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class IndexHasChangedCommand extends RavenCommand
{
    private array $definition;

    public function __construct(?DocumentConventions $conventions, ?IndexDefinition $definition)
    {
        parent::__construct(null);

        $this->definition = $this->getMapper()->normalize($definition);
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/indexes/has-changed";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->definition,
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

        $deserializedResponse = $this->getMapper()->deserialize($response, IndexHasChangedResponse::class, 'json');
        $this->result = $deserializedResponse->isChanged();
    }
}
