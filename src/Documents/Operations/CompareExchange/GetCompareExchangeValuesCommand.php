<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Exceptions\InvalidResultAssignedToCommandException;
use RavenDB\Utils\UrlUtils;
use RavenDB\Http\ServerNode;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\RavenCommand;
use RavenDB\Utils\StringUtils;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Documents\Conventions\DocumentConventions;
use ReflectionException;

class GetCompareExchangeValuesCommand extends RavenCommand
{
    private ?GetCompareExchangeValuesOperation $operation = null;
    private bool $materializeMetadata = false;
    private ?DocumentConventions $conventions = null;

    public function __construct(?GetCompareExchangeValuesOperation $operation, bool $materializeMetadata, ?DocumentConventions $conventions)
    {
        parent::__construct(CompareExchangeValueArray::class);

        $this->operation           = $operation;
        $this->materializeMetadata = $materializeMetadata;
        $this->conventions         = $conventions;
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . '/databases/' . $serverNode->getDatabase() . '/cmpxchg?';

        if ($this->operation->getKeys() != null) {
            foreach ($this->operation->getKeys() as $key) {
                $url .= '&key=' . UrlUtils::escapeDataString($key);
            }
        } else {
            if (StringUtils::isNotEmpty($this->operation->getStartWith())) {
                $url .= '&startsWith=' . UrlUtils::escapeDataString($this->operation->getStartWith());
            }

            if ($this->operation->getStart() != null) {
                $url .= '&start=' . $this->operation->getStart();
            }

            if ($this->operation->getPageSize() != null) {
                $url .= '&pageSize=' . $this->operation->getPageSize();
            }
        }

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    /**
     * @throws ReflectionException
     * @throws InvalidResultAssignedToCommandException
     */
    public function setResponse(?string $response, bool $fromCache): void
    {
        $responseArray = $response != null ? json_decode($response, true) : null;
        $result = CompareExchangeValueResultParser::getValues($this->operation->getClassName(), $responseArray, $this->materializeMetadata, $this->conventions);
        $this->setResult($result);
    }

}
