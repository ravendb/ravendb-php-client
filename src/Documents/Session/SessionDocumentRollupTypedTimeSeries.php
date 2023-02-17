<?php

namespace RavenDB\Documents\Session;

use DateTime;
use RavenDB\Constants\PhpClient;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntry;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntryArray;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesEntryArray;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesRollupEntry;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesRollupEntryArray;

class SessionDocumentRollupTypedTimeSeries extends SessionTimeSeriesBase implements SessionDocumentRollupTypedTimeSeriesInterface
{
    private ?string $className;

    public function __construct(?string $className, ?InMemoryDocumentSessionOperations $session, object|string|null $idOrEntity, ?string $name)
    {
        parent::__construct($session, $idOrEntity, $name);
        $this->className = $className;
    }

    function get(?DateTime $from = null, ?DateTime $to = null, int $start = 0, int $pageSize = PhpClient::INT_MAX_VALUE): ?TypedTimeSeriesRollupEntryArray
    {
        if ($this->notInCache($from, $to)) {
            /** @var TimeSeriesEntryArray $results */
            $results = $this->getTimeSeriesAndIncludes($from, $to, null, $start, $pageSize);
            if ($results == null) {
                return null;
            }

            return TypedTimeSeriesRollupEntryArray::fromArray(array_map(function(TimeSeriesEntry $e) { return TypedTimeSeriesRollupEntry::fromEntry($this->className, $e); }, $results->getArrayCopy()));
        }

        $results = $this->getFromCache($from, $to, null, $start, $pageSize);
        return TypedTimeSeriesRollupEntryArray::fromArray(array_map(function(TimeSeriesEntry $e) { return TypedTimeSeriesRollupEntry::fromEntry($this->className, $e); }, $results->getArrayCopy()));
    }

    public function appendEntry(TypedTimeSeriesRollupEntry $entry): void
    {
        $values = $entry->getValuesFromMembers();
        parent::append($entry->getTimestamp(), $values, $entry->getTag());
    }
}
