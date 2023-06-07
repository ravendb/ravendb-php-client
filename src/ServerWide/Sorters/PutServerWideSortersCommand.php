<?php

namespace RavenDB\ServerWide\Sorters;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Queries\Sorting\SorterDefinitionArray;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;

class PutServerWideSortersCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private ?array $sortersToAdd = null;

    public function __construct(?DocumentConventions $conventions, ?SorterDefinitionArray $sortersToAdd)
    {
        parent::__construct();

        if ($conventions == null) {
            throw new IllegalArgumentException("Conventions cannot be null");
        }

        if ($sortersToAdd == null) {
            throw new IllegalArgumentException("SortersToAdd cannot be null");
        }

            $this->sortersToAdd = [];

            for ($i = 0; $i < count($sortersToAdd); $i++) {
                if ($sortersToAdd[$i]->getName() == null) {
                    throw new IllegalArgumentException("Sorter name cannot be null");
                }

                $this->sortersToAdd[$i] = $this->getMapper()->normalize($sortersToAdd[$i]);
            }
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/admin/sorters";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => [
                'Sorters' => $this->sortersToAdd
            ],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
    }

    public
    function isReadRequest(): bool
    {
        return false;
    }

    public
    function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }

}
