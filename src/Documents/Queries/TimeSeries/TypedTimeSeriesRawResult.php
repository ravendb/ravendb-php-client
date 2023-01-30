<?php

namespace RavenDB\Documents\Queries\TimeSeries;

use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesEntryArray;
use Symfony\Component\Serializer\Annotation\SerializedName;

class TypedTimeSeriesRawResult extends TimeSeriesQueryResult
{
    #[SerializedName("Results")]
    private ?TypedTimeSeriesEntryArray $results = null;

    public function getResults(): ?TypedTimeSeriesEntryArray
    {
        return $this->results;
    }

    public function setResults(?TypedTimeSeriesEntryArray $results): void
    {
        $this->results = $results;
    }
}
