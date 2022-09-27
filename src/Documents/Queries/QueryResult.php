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
//        queryResult.setIndexName(getIndexName());
//        queryResult.setIndexTimestamp(getIndexTimestamp());
//        queryResult.setIncludedPaths(getIncludedPaths());
//        queryResult.setStale(isStale());
        $queryResult->setSkippedResults($this->getSkippedResults());
        $queryResult->setTotalResults($this->getTotalResults());
        $queryResult->setLongTotalResults($this->getLongTotalResults());
        $queryResult->setHighlightings($this->getHighlightings());
//        queryResult.setExplanations(getExplanations() != null ? new HashMap<>(getExplanations()) : null);
//        queryResult.setTimings(getTimings());
//        queryResult.setLastQueryTime(getLastQueryTime());
        $queryResult->setDurationInMs($this->getDurationInMs());
//        queryResult.setResultEtag(getResultEtag());
//        queryResult.setNodeTag(getNodeTag());
//        queryResult.setCounterIncludes(getCounterIncludes());
//        queryResult.setIncludedCounterNames(getIncludedCounterNames());
//        queryResult.setTimeSeriesIncludes(getTimeSeriesIncludes());
        $queryResult->setCompareExchangeValueIncludes($this->getCompareExchangeValueIncludes());
        return $queryResult;
    }
}
