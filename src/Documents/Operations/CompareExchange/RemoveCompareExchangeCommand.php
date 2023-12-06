<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Utils\UrlUtils;
use RavenDB\Http\ServerNode;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\RavenCommand;
use RavenDB\Utils\StringUtils;
use RavenDB\Utils\RaftIdGenerator;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;

class RemoveCompareExchangeCommand extends RavenCommand
{
    private ?string $className = null;
    private ?string $key = null;
    private ?int $index = null;
    private ?DocumentConventions $conventions = null;

    public function __construct($className, ?string $key, ?int $index, ?DocumentConventions $conventions)
    {
        parent::__construct(CompareExchangeResult::class);

        if (StringUtils::isEmpty($key)) {
            throw new IllegalArgumentException('The kye argument must have value');
        }

        $this->className   = $className;
        $this->key         = $key;
        $this->index       = $index;
        $this->conventions = $conventions;
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . '/databases/' . $serverNode->getDatabase() . '/cmpxchg?key=' . UrlUtils::escapeDataString($this->key) . "&index=" . $this->index;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::DELETE);
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function setResponse(?string $response, bool $fromCache): void
    {
        $this->result = CompareExchangeResult::parseFromString($this->className, $response, $this->conventions);
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
