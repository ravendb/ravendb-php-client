<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Documents\Conventions\DocumentConventions;

class GetCompareExchangeValueOperation implements OperationInterface
{
    private ?string $key = null;
    private bool $materializeMetadata = false;
    private ?string $className = null;

    public function __construct(?string $className, ?string $key, bool $materializeMetadata = true)
    {
        $this->className = $className;
        $this->key = $key;
        $this->materializeMetadata = $materializeMetadata;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new GetCompareExchangeValueCommand($this->className, $this->key, $this->materializeMetadata, $conventions);
    }
}
