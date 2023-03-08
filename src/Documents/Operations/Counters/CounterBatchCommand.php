<?php

namespace RavenDB\Documents\Operations\Counters;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class CounterBatchCommand extends RavenCommand
{
    private ?CounterBatch $counterBatch = null;

    public function __construct(?CounterBatch $counterBatch)
    {
        parent::__construct(CountersDetail::class);

        if ($counterBatch == null) {
            throw new IllegalArgumentException("CounterBatch cannot be null");
        }

        $this->counterBatch = $counterBatch;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/counters";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->getMapper()->normalize($this->counterBatch),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            return;
        }

        $this->result = $this->getMapper()->deserialize($response, CountersDetail::class, 'json');
    }
}
