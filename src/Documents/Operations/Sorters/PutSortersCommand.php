<?php

namespace RavenDB\Documents\Operations\Sorters;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Queries\Sorting\SorterDefinitionArray;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;

class PutSortersCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private ?SorterDefinitionArray $sortersToAdd = null;

    public function __construct(?DocumentConventions $conventions, ?SorterDefinitionArray $sortersToAdd)
    {
        parent::__construct();
        if ($conventions == null) {
            throw new IllegalArgumentException("Conventions cannot be null");
        }

        if ($sortersToAdd == null) {
            throw new IllegalArgumentException("SortersToAdd cannot be null");
        }

        if ($sortersToAdd->containsValue(null)) {
            throw new IllegalArgumentException("Sorter cannot be null");
        }

        $this->sortersToAdd = $sortersToAdd;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/admin/sorters";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => [
                'Sorters' => $this->sortersToAdd->getArrayCopy()
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
    }
}
