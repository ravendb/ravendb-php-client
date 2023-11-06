<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Documents\Conventions\DocumentConventions;

class DeleteCompareExchangeValueOperation implements OperationInterface
{
    private ?string $className = null;
    private ?string $key = null;
    private ?int $index = null;

    public function __construct(?string $className, ?string $key, ?int $index)
    {
        $this->key = $key;
        $this->index = $index;
        $this->className = $className;
    }


    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new RemoveCompareExchangeCommand($this->className, $this->key, $this->index, $conventions);
    }
}
