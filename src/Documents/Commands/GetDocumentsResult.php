<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Http\ResultInterface;

class GetDocumentsResult implements ResultInterface
{
    private array $includes = [];
    private array $results = [];

    // @todo: implement this properties with getter and setters in tGetDocumentsResult
//    private ObjectNode $counterIncludes;
//    private ObjectNode $timeSeriesIncludes;
//    private ObjectNode $compareExchangeValueIncludes;
//    private int $nextPageStart;

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function getIncludes(): array
    {
        return $this->includes;
    }

    public function setIncludes(array $includes): void
    {
        $this->includes = $includes;
    }
}
