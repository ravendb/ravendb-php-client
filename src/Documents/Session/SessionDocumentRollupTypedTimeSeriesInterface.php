<?php

namespace RavenDB\Documents\Session;

use DateTime;
use RavenDB\Constants\PhpClient;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesRollupEntryArray;

interface SessionDocumentRollupTypedTimeSeriesInterface extends
    SessionDocumentRollupTypedAppendTimeSeriesBaseInterface,
    SessionDocumentDeleteTimeSeriesBaseInterface
{
    function get(?DateTime $from = null, ?DateTime $to = null, int $start = 0, int $pageSize = PhpClient::INT_MAX_VALUE): ?TypedTimeSeriesRollupEntryArray;
}
