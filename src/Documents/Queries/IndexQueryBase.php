<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Parameters;
use RavenDB\Type\Duration;
use RavenDB\Utils\HashUtils;

class IndexQueryBase implements IndexQueryInterface
{
    private int $pageSize = PHP_INT_MAX;
    private bool $pageSizeSet = false;
    private ?string $query = null;
    private ?Parameters $queryParameters = null;
    private ?ProjectionBehavior $projectionBehavior = null;
    private int $start = 0;
    private bool $waitForNonStaleResults = false;
    private ?Duration $waitForNonStaleResultsTimeout = null;

    /**
     * Whether the page size was explicitly set or still at its default value
     * @return bool true if page size is set
     */
    public function isPageSizeSet(): bool
    {
        return $this->pageSizeSet;
    }

    /**
     * Actual query that will be performed (RQL syntax)
     * @return string Index query
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * Actual query that will be performed (RQL syntax)
     * @param string|null $query Sets the value
     */
    public function setQuery(?string $query): void
    {
        $this->query = $query;
    }

    public function getQueryParameters(): ?Parameters
    {
        return $this->queryParameters;
    }

    public function setQueryParameters(Parameters $queryParameters): void
    {
        $this->queryParameters = $queryParameters;
    }

    public function getProjectionBehavior(): ?ProjectionBehavior
    {
        return $this->projectionBehavior;
    }

    public function setProjectionBehavior(?ProjectionBehavior $projectionBehavior): void
    {
        $this->projectionBehavior = $projectionBehavior;
    }

    /**
     * Number of records that should be skipped.
     * @deprecated use OFFSET in RQL instead
     * @return int items to skip
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * Number of records that should be skipped.
     * @deprecated use OFFSET in RQL instead
     * @param int $start Sets amount of items to skip
     */
    public function setStart(int $start): void
    {
        $this->start = $start;
    }

    /**
     * Maximum number of records that will be retrieved.
     * @deprecated use LIMIT in RQL instead
     * @return int page size
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Maximum number of records that will be retrieved.
     * @deprecated use LIMIT in RQL instead
     * @param int $pageSize Sets the value
     */
    public function setPageSize(int $pageSize): void
    {
        $this->pageSize = $pageSize;
        $this->pageSizeSet = true;
    }

    /**
     * When set to true server side will wait until result are non stale or until timeout
     * @return bool true if server should wait for non stale results
     */
    public function isWaitForNonStaleResults(): bool
    {
        return $this->waitForNonStaleResults;
    }

    /**
     * When set to true server side will wait until result are non stale or until timeout
     * @param bool $waitForNonStaleResults Sets the valueQer
     */
    public function setWaitForNonStaleResults(bool $waitForNonStaleResults): void
    {
        $this->waitForNonStaleResults = $waitForNonStaleResults;
    }

    public function getWaitForNonStaleResultsTimeout(): ?Duration
    {
        return $this->waitForNonStaleResultsTimeout;
    }

    public function setWaitForNonStaleResultsTimeout(?Duration $waitForNonStaleResultsTimeout): void
    {
        $this->waitForNonStaleResultsTimeout = $waitForNonStaleResultsTimeout;
    }

    public function toString(): string
    {
        return $$this->query;
    }

    public function equals(?object &$o): bool {
        if ($this == $o) return true;
        if (($o == null) || (get_class($this) != get_class($o))) return false;

        /** @var IndexQueryBase $that */
        $that = $o;

        if ($this->pageSize != $that->pageSize) return false;
        if ($this->pageSizeSet != $that->pageSizeSet) return false;
        if ($this->start != $that->start) return false;
        if ($this->waitForNonStaleResults != $that->waitForNonStaleResults) return false;
        if (($this->query != null) ? (strcmp($this->query, $that->query)) : ($that->query != null)) return false;
        return $this->waitForNonStaleResultsTimeout != null ? $this->waitForNonStaleResultsTimeout->equals($that->waitForNonStaleResultsTimeout) : $that->waitForNonStaleResultsTimeout == null;
    }

//    @ignore this method
//    public function hashCode(): int
//    {
//        $result = $this->pageSize;
//        $result = 31 * $result + ($this->pageSizeSet ? 1 : 0);
//        $result = 31 * $result + ($this->query != null ? HashUtils::hashCode($this->query) : 0);
//        $result = 31 * $result + $this->start;
//        $result = 31 * $result + ($this->waitForNonStaleResults ? 1 : 0);
//        $result = 31 * $result + ($this->waitForNonStaleResultsTimeout != null ? $this->waitForNonStaleResultsTimeout->hashCode() : 0);
//        return $result;
//    }
}
