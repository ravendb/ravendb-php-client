<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Extensions\EntityMapper;
use RuntimeException;

//! status: DONE
class IndexQuery extends IndexQueryWithParameters
{
    public function __construct(string $query = '')
    {
        if (!empty($query)) {
            $this->setQuery($query);
        }
    }

    private bool $disableCaching = false;

    /**
     * Indicates if query results should be read from cache (if cached previously) or added to cache (if there were no cached items prior)
     * @return bool true if caching was disabled
     */
    public function isDisableCaching(): bool
    {
        return $this->disableCaching;
    }

    /**
     * Indicates if query results should be read from cache (if cached previously) or added to cache (if there were no cached items prior)
     * @param bool $disableCaching sets the value
     */
    public function setDisableCaching(bool $disableCaching): void
    {
        $this->disableCaching = $disableCaching;
    }

    public function getQueryHash(EntityMapper $serializer): string
    {
        $hasher = new HashCalculator();
        try {
            $hasher->write($this->getQuery());
            $hasher->write($this->isWaitForNonStaleResults());
            $hasher->write($this->isSkipDuplicateChecking());
            $hasher->write($this->getWaitForNonStaleResultsTimeout() ? $this->getWaitForNonStaleResultsTimeout()->toMillis() : 0);
            $hasher->write($this->getStart());
            $hasher->write($this->getPageSize());
            $hasher->write($this->getQueryParameters(), $serializer);
            return $hasher->getHash();
        } catch (\Throwable $e) {
            throw new RuntimeException("Unable to calculate hash", $e->getCode(), $e);
        }
    }

    public function equals(?object &$o): bool
    {
        if ($this == $o) return true;
        if ($o == null || get_class($this) != get_class($o)) return false;
        if (!parent::equals($o)) return false;

        /** @var IndexQuery $that */
        $that = $o;

        return $this->disableCaching == $that->disableCaching;
    }

//    @todo: ignore this method
//    public int hashCode() {
//        int result = super.hashCode();
//        result = 31 * result + (disableCaching ? 1 : 0);
//        return result;
//    }
}
