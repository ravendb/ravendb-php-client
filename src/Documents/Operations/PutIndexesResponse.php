<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Indexes\PutIndexResultArray;
use RavenDB\Http\ResultInterface;

class PutIndexesResponse implements ResultInterface
{
    private ?PutIndexResultArray $results = null;

    public function getResults(): PutIndexResultArray
    {
        return $this->results;
    }

    public function setResults(PutIndexResultArray $results): void
    {
        $this->results = $results;
    }
}
