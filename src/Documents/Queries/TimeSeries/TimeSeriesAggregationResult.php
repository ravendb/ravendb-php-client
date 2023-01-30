<?php

namespace RavenDB\Documents\Queries\TimeSeries;

use Symfony\Component\Serializer\Annotation\SerializedName;

class TimeSeriesAggregationResult extends TimeSeriesQueryResult
{
    #[SerializedName("Results")]
    private ?TimeSeriesRangeAggregationArray $results = null;

    public function getResults(): ?TimeSeriesRangeAggregationArray
    {
        return $this->results;
    }

    public function setResults(?TimeSeriesRangeAggregationArray $results): void
    {
        $this->results = $results;
    }

    public function asTypedResult(string $className): TypedTimeSeriesAggregationResult
    {
        $result = new TypedTimeSeriesAggregationResult();
        $result->setCount($this->getCount());

        $r = array_map(function($x) use ($className) {return $x->asTypedEntry($className);},  $this->results->getArrayCopy());
        $result->setResults(TypedTimeSeriesRangeAggregationArray::fromArray($r));

        return $result;
    }
}
