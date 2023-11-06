<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Http\ResultInterface;

class QueryResult extends GenericQueryResult implements ResultInterface
{
  /**
     * Creates a snapshot of the query results
     * @return QueryResult returns snapshot of query result
     */
    public function createSnapshot(): QueryResult
    {
        $queryResult = new QueryResult();
        $queryResult->setResults($this->getResults());
        $queryResult->setIncludes($this->getIncludes());
        $queryResult->setIndexName($this->getIndexName());
        $queryResult->setIndexTimestamp($this->getIndexTimestamp());
        $queryResult->setIncludedPaths($this->getIncludedPaths());
        $queryResult->setStale($this->isStale());
        $queryResult->setSkippedResults($this->getSkippedResults());
        $queryResult->setTotalResults($this->getTotalResults());
        $queryResult->setLongTotalResults($this->getLongTotalResults());
        $queryResult->setHighlightings($this->getHighlightings());
        $queryResult->setExplanations($this->getExplanations());
        $queryResult->setTimings($this->getTimings());
        $queryResult->setLastQueryTime($this->getLastQueryTime());
        $queryResult->setDurationInMs($this->getDurationInMs());
        $queryResult->setResultEtag($this->getResultEtag());
        $queryResult->setNodeTag($this->getNodeTag());
        $queryResult->setCounterIncludes($this->getCounterIncludes());
        $queryResult->setRevisionIncludes($this->getRevisionIncludes());
        $queryResult->setIncludedCounterNames($this->getIncludedCounterNames());
        $queryResult->setTimeSeriesIncludes($this->getTimeSeriesIncludes());
        $queryResult->setCompareExchangeValueIncludes($this->getCompareExchangeValueIncludes());
        return $queryResult;
    }
}
