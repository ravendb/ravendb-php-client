<?php

namespace RavenDB\Documents\Session;

use Closure;
use DateTimeInterface;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntry;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntryArray;

/**
 * Time series synchronous session operations
 */
interface SessionDocumentTimeSeriesInterface extends
    SessionDocumentAppendTimeSeriesBaseInterface,
    SessionDocumentDeleteTimeSeriesBaseInterface
{
    /**
     * Return the time series values for the provided range
     *
     * @return TimeSeriesEntryArray|null time series values
     */
    public function get(?DateTimeInterface $from = null, ?DateTimeInterface $to = null, ?Closure $includes = null, int $start = 0, int $pageSize = PHP_INT_MAX): ?TimeSeriesEntryArray;
}
