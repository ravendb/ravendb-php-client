<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Http\ResultInterface;
use RavenDB\Json\MetadataAsDictionary;
use RavenDB\Documents\Session\MetadataDictionaryInterface;



/**
 * @template T
 * @implements CompareExchangeValueInterface<T>
 */
class CompareExchangeValue implements CompareExchangeValueInterface, ResultInterface
{
    private ?string $key = null;
    private ?int $index = null;
    /** @var T */
    private $value;
    private ?MetadataDictionaryInterface $metadataAsDictionary = null;


    /**
     * @param string|null                      $key
     * @param int                              $index
     * @param T                                $value
     * @param MetadataDictionaryInterface|null $metadata
     */
    public function __construct(?string $key, int $index, $value, ?MetadataDictionaryInterface $metadata = null)
    {
        $this->key = $key;
        $this->index = $index;
        $this->value = $value;
        $this->metadataAsDictionary = $metadata;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): void
    {
        $this->key = $key;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function setIndex(?int $index): void
    {
        $this->index = $index;
    }

    /**
     * @return T
     */
    public function & getValue()
    {
        return $this->value;
    }

    /**
     * @param T $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function & getMetadata(): ?MetadataDictionaryInterface
    {
        if ($this->metadataAsDictionary == null) {
            $this->metadataAsDictionary = new MetadataAsDictionary();
        }
        return $this->metadataAsDictionary;
    }

    public function hasMetadata(): bool
    {
        return $this->metadataAsDictionary != null;
    }
}
