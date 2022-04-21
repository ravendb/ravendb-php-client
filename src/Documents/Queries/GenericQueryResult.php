<?php

namespace RavenDB\Documents\Queries;

use Symfony\Component\Serializer\Annotation\SerializedName;

class GenericQueryResult extends QueryResultBase
{
    /** @SerializedName("TotalResults"); */
    private int $totalResults = 0;

    /** @SerializedName("LongTotalResults"); */
    private int $longTotalResults = 0;

    private int $cappedMaxResults;

    /** @SerializedName("SkippedResults"); */
    private int $skippedResults;

//    private Map<String, Map<String, String[]>> highlightings;

//    private Map<String, String[]> explanations;

    /** @SerializedName("DurationInMs"); */
    private int $durationInMs;

    /** @ SerializedName("ResultSize"); */
    private int $resultSize;

    /**
     * Gets the total results for this query
     * @return int Total results for given query
     */
    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    /**
     * Sets the total results for this query
     * @param int $totalResults Sets the total results
     */
    public function setTotalResults(int $totalResults): void
    {
        $this->totalResults = $totalResults;
    }

    /**
     * Gets the total results as long for this query
     * @return int Total results as long for this query
     */
    public function getLongTotalResults(): int
    {
        return $this->longTotalResults;
    }

    /**
     * Sets the total results as long for this query
     * @param int $longTotalResults Total result as long for this query
     */
    public function setLongTotalResults(int $longTotalResults): void
    {
        $this->longTotalResults = $longTotalResults;
    }

//    /**
//     * Gets the total results for the query, taking into account the
//     * offset / limit clauses for this query
//     * @return Total results
//     */
//    public Integer getCappedMaxResults() {
//        return cappedMaxResults;
//    }
//
//    /**
//     * Sets the total results for the query, taking into account the
//     * offset / limit clauses for this query
//     * @param cappedMaxResults total results
//     */
//    public void setCappedMaxResults(Integer cappedMaxResults) {
//        this.cappedMaxResults = cappedMaxResults;
//    }
//
//    /**
//     * Gets the skipped results
//     * @return Amount of skipped results
//     */
//    public int getSkippedResults() {
//        return skippedResults;
//    }
//
//    /**
//     * Sets the skipped results
//     * @param skippedResults Sets the skipped results
//     */
//    public void setSkippedResults(int skippedResults) {
//        this.skippedResults = skippedResults;
//    }
//
//    /**
//     * @return Highlighter results (if requested).
//     */
//    public Map<String, Map<String, String[]>> getHighlightings() {
//        return highlightings;
//    }
//
//    /**
//     * @param highlightings Highlighter results (if requested).
//     */
//    public void setHighlightings(Map<String, Map<String, String[]>> highlightings) {
//        this.highlightings = highlightings;
//    }
//
//    /**
//     * @return Explanations (if requested).
//     */
//    public Map<String, String[]> getExplanations() {
//        return explanations;
//    }
//
//    /**
//     * @param explanations Explanations (if requested).
//     */
//    public void setExplanations(Map<String, String[]> explanations) {
//        this.explanations = explanations;
//    }
//
//    /**
//     * The duration of actually executing the query server side
//     * @return Query duration in milliseconds
//     */
//    public long getDurationInMs() {
//        return durationInMs;
//    }
//
//    /**
//     * The duration of actually executing the query server side
//     * @param durationInMs Sets the query duration
//     */
//    public void setDurationInMs(long durationInMs) {
//        this.durationInMs = durationInMs;
//    }
//
//    /**
//     * The size of the request which were sent from the server.
//     * This value is the _uncompressed_ size.
//     * @deprecated ResultSize is not supported anymore. Will be removed in next major version of the product.
//     * @return uncompressed result size
//     */
//    @Deprecated
//    public long getResultSize() {
//        return resultSize;
//    }
//
//    /**
//     * The size of the request which were sent from the server.
//     * This value is the _uncompressed_ size.
//     * @deprecated ResultSize is not supported anymore. Will be removed in next major version of the product.
//     * @param resultSize Sets the result size
//     */
//    @Deprecated
//    public void setResultSize(long resultSize) {
//        this.resultSize = resultSize;
//    }
}
