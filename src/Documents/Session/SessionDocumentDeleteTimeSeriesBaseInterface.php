<?php

namespace RavenDB\Documents\Session;

use DateTimeInterface;

interface SessionDocumentDeleteTimeSeriesBaseInterface
{
    /**
     * Delete all the values in the time series in the range of from .. to.
     * If $to is not set it delete the value in the time series in the specified time stamp.
     * If both values are not set, it will delete all the values in the time series.
     * @param ?DateTimeInterface $from range start
     * @param ?DateTimeInterface $to range end
     */
    function delete(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): void;
}
