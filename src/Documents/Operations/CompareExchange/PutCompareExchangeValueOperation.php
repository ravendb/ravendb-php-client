<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\MetadataDictionaryInterface;




/**
 * @template T
 */
class PutCompareExchangeValueOperation implements OperationInterface
{
    private ?string $key = null;
    /** @var ?T  */
    private $value = null;
    private ?int $index = null;
    private ?MetadataDictionaryInterface $metadata = null;

    /**
     * @param string|null                      $key
     * @param ?T                               $value
     * @param int                              $index
     * @param MetadataDictionaryInterface|null $metadata
     */
    public function __construct(?string $key, $value, int $index, ?MetadataDictionaryInterface $metadata = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->index = $index;
        $this->metadata = $metadata;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new PutCompareExchangeValueCommand($this->key, $this->value, $this->index, $this->metadata, $conventions);
    }
}
