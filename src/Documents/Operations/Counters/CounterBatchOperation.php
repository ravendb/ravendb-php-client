<?php

namespace RavenDB\Documents\Operations\Counters;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;

class CounterBatchOperation implements OperationInterface
{
    private ?CounterBatch $counterBatch = null;

    public function __construct(?CounterBatch $counterBatch)
    {
        $this->counterBatch = $counterBatch;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new CounterBatchCommand($this->counterBatch);
    }
}
