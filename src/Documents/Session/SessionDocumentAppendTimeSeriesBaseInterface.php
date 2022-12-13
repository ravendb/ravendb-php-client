<?php

namespace RavenDB\Documents\Session;

use DateTime;

interface SessionDocumentAppendTimeSeriesBaseInterface
{
    /**
     * Append a single value or the values (and optional tag) to the times series at the provided time stamp
     */
    public function append(DateTime $timestamp, float|array $values, ?string $tag = null): void;
}
