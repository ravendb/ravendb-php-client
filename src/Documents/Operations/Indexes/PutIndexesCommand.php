<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\IndexDefinitionArray;
use RavenDB\Documents\Indexes\IndexTypeExtensions;
use RavenDB\Documents\Indexes\PutIndexResultArray;
use RavenDB\Documents\Operations\PutIndexesResponse;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;

class PutIndexesCommand extends RavenCommand implements RaftCommandInterface
{
    private array $indexToAdd;

    private bool $allJavaScriptIndexes = false;

    public function __construct(?DocumentConventions $conventions, ?IndexDefinitionArray $indexesToAdd)
    {
        parent::__construct(PutIndexResultArray::class);

        if ($conventions == null) {
            throw new IllegalArgumentException("conventions cannot be null");
        }

        if ($indexesToAdd == null) {
            throw new IllegalArgumentException("indexesToAdd cannot be null");
        }

        $this->indexToAdd = [];
        $this->allJavaScriptIndexes = true;

        foreach ($indexesToAdd as $index) {
            //We validate on the server that it is indeed a javascript index.

            if (!IndexTypeExtensions::isJavaScript($index->getType())) {
                $this->allJavaScriptIndexes = false;
            }

            if ($index->getName() == null) {
                throw new IllegalArgumentException("Index name cannot be null");
            }
            $this->indexToAdd[] = $index;
        }
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase();
        $url .= $this->allJavaScriptIndexes ? "/indexes" : "/admin/indexes";

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {

        $options = [
            'json' => [
                'Indexes' => $this->getMapper()->normalize($this->indexToAdd)
            ],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        /** @var PutIndexesResponse $decodedResponse */
        $decodedResponse = $this->getMapper()->deserialize($response, PutIndexesResponse::class, 'json');
        $this->result = $decodedResponse->getResults();
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
