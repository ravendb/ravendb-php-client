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
//        queryResult.setIncludes(getIncludes());
//        queryResult.setIndexName(getIndexName());
//        queryResult.setIndexTimestamp(getIndexTimestamp());
//        queryResult.setIncludedPaths(getIncludedPaths());
//        queryResult.setStale(isStale());
//        queryResult.setSkippedResults(getSkippedResults());
//        queryResult.setTotalResults(getTotalResults());
//        queryResult.setLongTotalResults(getLongTotalResults());
//        queryResult.setHighlightings(getHighlightings() != null ? new HashMap<>(getHighlightings()) : null);
//        queryResult.setExplanations(getExplanations() != null ? new HashMap<>(getExplanations()) : null);
//        queryResult.setTimings(getTimings());
//        queryResult.setLastQueryTime(getLastQueryTime());
//        queryResult.setDurationInMs(getDurationInMs());
//        queryResult.setResultEtag(getResultEtag());
//        queryResult.setNodeTag(getNodeTag());
//        queryResult.setCounterIncludes(getCounterIncludes());
//        queryResult.setIncludedCounterNames(getIncludedCounterNames());
//        queryResult.setTimeSeriesIncludes(getTimeSeriesIncludes());
//        queryResult.setCompareExchangeValueIncludes(getCompareExchangeValueIncludes());
        return $queryResult;
    }
}
