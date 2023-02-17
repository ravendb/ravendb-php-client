<?php

namespace RavenDB\Documents\Queries\TimeSeries;

use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntryArray;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesEntryArray;
use Symfony\Component\Serializer\Annotation\SerializedName;

class TimeSeriesRawResult extends TimeSeriesQueryResult
{
    #[SerializedName("Results")]
    private ?TimeSeriesEntryArray $results = null;

    public function getResults(): ?TimeSeriesEntryArray
    {
        return $this->results;
    }

    public function setResults(?TimeSeriesEntryArray $results): void
    {
        $this->results = $results;
    }

    public function asTypedResult(string $className): TypedTimeSeriesRawResult
    {
        $result = new TypedTimeSeriesRawResult();
        $result->setCount(parent::getCount());
        $a = array_map(function($x) use ($className) { return $x->asTypedEntry($className);}, $this->getResults()->getArrayCopy());
        $result->setResults(TypedTimeSeriesEntryArray::fromArray($a));
        return $result;
    }
}
