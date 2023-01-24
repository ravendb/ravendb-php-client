<?php

namespace RavenDB\Documents\Session;

use DateTimeInterface;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesEntry;

interface SessionDocumentTypedAppendTimeSeriesBaseInterface
{
    function append(?DateTimeInterface $timestamp, mixed $entry, ?string $tag = null): void;
    function appendEntry(TypedTimeSeriesEntry $entry): void;
}
