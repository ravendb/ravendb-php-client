<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\VoidOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpCache;
use RavenDB\Http\VoidRavenCommand;

class TimeSeriesBatchOperation implements VoidOperationInterface
{
    private ?string $documentId = null;
    private ?TimeSeriesOperation $operation = null;

    public function __construct(?string $documentId, ?TimeSeriesOperation $operation)
    {
        if ($documentId == null) {
            throw new IllegalArgumentException("Document id cannot be null");
        }
        if ($operation == null) {
            throw new IllegalArgumentException("Operation cannot be null");
        }

        $this->documentId = $documentId;
        $this->operation = $operation;
    }

    function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): VoidRavenCommand
    {
        return new TimeSeriesBatchCommand($this->documentId, $this->operation, $conventions);
    }
}
