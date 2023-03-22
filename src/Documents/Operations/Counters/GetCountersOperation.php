<?php

namespace RavenDB\Documents\Operations\Counters;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;
use RavenDB\Type\StringArray;

class GetCountersOperation implements OperationInterface
{
    private ?string $docId = null;
    private ?StringArray $counters = null;
    private bool $returnFullResults = false;

    public function __construct(?string $docId, string|StringArray|array|null $counters = null, bool $returnFullResults = false)
    {
        if ($counters == null) {
            $counters = [];
        }

        if (is_string($counters)) {
            $counters = [$counters];
        }

        if (is_array($counters)) {
            $counters = StringArray::fromArray($counters);
        }

        $this->docId = $docId;
        $this->counters = $counters;
        $this->returnFullResults = $returnFullResults;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new GetCounterValuesCommand($this->docId, $this->counters, $this->returnFullResults, $conventions);
    }
}
