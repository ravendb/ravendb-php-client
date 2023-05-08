<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Http\ServerNode;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\RavenCommand;
use RavenDB\Utils\StringUtils;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;

class GetCompareExchangeValueCommand extends RavenCommand
{
    private ?string $key = null;
    private bool $materializeMetadata = false;
    private ?string $className = null;
    private ?DocumentConventions $conventions = null;

    public function __construct(?string $className, ?string $key, bool $materializeMetadata, ?DocumentConventions $conventions)
    {
        parent::__construct(CompareExchangeValue::class);

        if (StringUtils::isEmpty($key)) {
            throw new IllegalArgumentException('The key argument must have value');
        }

        $this->key = $key;
        $this->className = $className;
        $this->materializeMetadata = $materializeMetadata;
        $this->conventions = $conventions;
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . '/databases/' . $serverNode->getDatabase() . '/cmpxchg?key=' . $this->urlEncode($this->key);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    /**
     * @throws \ReflectionException
     * @throws \RavenDB\Exceptions\InvalidResultAssignedToCommandException
     */
    public function setResponse(?string $response, bool $fromCache): void
    {
        $responseArray = $response != null ? json_decode($response, true) : null;
        $this->result =  CompareExchangeValueResultParser::getValue($this->className, $responseArray, $this->materializeMetadata, $this->conventions);
    }
}
