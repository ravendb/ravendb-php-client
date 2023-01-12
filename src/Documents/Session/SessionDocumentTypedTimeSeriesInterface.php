<?php

namespace RavenDB\Documents\Session;

use DateTimeInterface;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesEntryArray;

/**
 * @template T
 */
interface SessionDocumentTypedTimeSeriesInterface extends
    SessionDocumentTypedAppendTimeSeriesBaseInterface,
    SessionDocumentDeleteTimeSeriesBaseInterface
{
    /**
     * Return the time series values for the provided range
     *
     * @param DateTimeInterface|null $from
     * @param DateTimeInterface|null $to
     * @param int $start
     * @param int $pageSize
     * @return TypedTimeSeriesEntryArray|null
     */
    public function get(?DateTimeInterface $from = null, ?DateTimeInterface $to = null, int $start = 0, int $pageSize = PHP_INT_MAX): ?TypedTimeSeriesEntryArray;
}
