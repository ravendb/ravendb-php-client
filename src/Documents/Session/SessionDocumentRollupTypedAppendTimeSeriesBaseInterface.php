<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesRollupEntry;

interface SessionDocumentRollupTypedAppendTimeSeriesBaseInterface
{
    function appendEntry(TypedTimeSeriesRollupEntry $entry): void;
}
