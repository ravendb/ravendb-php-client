<?php

namespace RavenDB\ServerWide\Operations\Analyzers;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\Analysis\AnalyzerDefinition;
use RavenDB\Documents\Indexes\Analysis\AnalyzerDefinitionArray;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;

class PutServerWideAnalyzersCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private ?array $analyzersToAdd = null;

    public function __construct(?DocumentConventions $conventions, ?AnalyzerDefinitionArray $analyzersToAdd)
    {
        parent::__construct();

        if ($conventions == null) {
            throw new IllegalArgumentException("Conventions cannot be null");
        }
        if ($analyzersToAdd == null) {
            throw new IllegalArgumentException("AnalyzersToAdd cannot be null");
        }

        $this->analyzersToAdd = [];

        /** @var AnalyzerDefinition $analyzer */
        foreach ($analyzersToAdd as $analyzer) {
            if ($analyzer->getName() == null) {
                throw new IllegalArgumentException("Name cannot be null");
            }

            $this->analyzersToAdd[] = $this->getMapper()->normalize($analyzer);
        }
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/admin/analyzers";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => [
                'Analyzers' => $this->analyzersToAdd
            ],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
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
