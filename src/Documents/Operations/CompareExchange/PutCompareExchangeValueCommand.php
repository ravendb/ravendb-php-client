<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Utils\UrlUtils;
use RavenDB\Http\ServerNode;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\RavenCommand;
use RavenDB\Utils\StringUtils;
use RavenDB\Utils\RaftIdGenerator;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Constants\CompareExchange;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Session\EntityToJson;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\MetadataDictionaryInterface;
use RavenDB\Documents\Operations\CompareExchangeSessionValue;

/**
 * @template T
 *
 * @extends RavenCommand<T>
 */
class PutCompareExchangeValueCommand extends RavenCommand
{

    private ?string $key = null;
    /** @var ?T */
    private $value = null;
    private ?int $index = null;
    private ?MetadataDictionaryInterface $metadata = null;
    private ?DocumentConventions $conventions = null;

    public function __construct(
        ?string                      $key,
                                     $value,
        int                          $index,
        ?MetadataDictionaryInterface $metadata,
        ?DocumentConventions         $conventions
    )
    {
        parent::__construct(CompareExchangeResult::class);

        if (StringUtils::isEmpty($key)) {
            throw new IllegalArgumentException('The key argument must have value');
        }

        if ($index < 0) {
            throw new IllegalStateException('Index must be a non-negative number');
        }

        $this->key         = $key;
        $this->value       = $value;
        $this->index       = $index;
        $this->metadata    = $metadata;
        $this->conventions = $conventions ?? DocumentConventions::getDefaultConventions();
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/cmpxchg?key=" . UrlUtils::escapeDataString($this->key) . "&index=" . $this->index;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $json = [
            CompareExchange::OBJECT_FIELD_NAME => EntityToJson::convertEntityToJsonStatic($this->value, $this->conventions, null, false)
        ];

        if ($this->metadata != null) {
            $metadata                     = CompareExchangeSessionValue::prepareMetadataForPut($this->key, $this->metadata, $this->conventions);
            $json[DocumentsMetadata::KEY] = $metadata;
        }

        $options = [
            'json'    => $json,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        $className    = is_object($this->value) ? get_class($this->value) : null;
        $this->result = CompareExchangeResult::parseFromString($className, $response, $this->conventions);
    }


    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
