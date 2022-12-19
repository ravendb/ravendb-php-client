<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;

class GetTimeSeriesStatisticsOperation implements OperationInterface
{
    private ?string $documentId = null;

    /**
     * Retrieve start, end and total number of entries for all time-series of a given document
     * @param ?string $documentId Document id
     */
    public function __construct(?string $documentId) {
        $this->documentId = $documentId;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new GetTimeSeriesStatisticsCommand($this->documentId);
    }
}
