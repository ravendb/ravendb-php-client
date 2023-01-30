<?php

namespace RavenDB\Documents\Queries\TimeSeries;

class TypedTimeSeriesAggregationResult extends TimeSeriesQueryResult
{
    private TypedTimeSeriesRangeAggregationArray $results;

    public function getResults(): TypedTimeSeriesRangeAggregationArray
    {
        return $this->results;
    }

    public function setResults(TypedTimeSeriesRangeAggregationArray $results): void
    {
        $this->results = $results;
    }
}
