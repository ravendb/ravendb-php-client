<?php

namespace RavenDB\Documents\Session;

use DateTimeInterface;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntry;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntryArray;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesValuesHelper;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesEntry;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesEntryArray;

/**
 * @template T
 */
class SessionDocumentTypedTimeSeries extends SessionTimeSeriesBase implements SessionDocumentTypedTimeSeriesInterface
{
    private ?string $className = null;

    public function __construct(string $className, ?InMemoryDocumentSessionOperations $session, object|string|null $idOrEntity, ?string $name)
    {
        parent::__construct($session, $idOrEntity, $name);
        $this->className = $className;
    }

    public function get(?DateTimeInterface $from = null, ?DateTimeInterface $to = null, int $start = 0, int $pageSize = PHP_INT_MAX): ?TypedTimeSeriesEntryArray
    {
        if ($this->notInCache($from, $to)) {
            /** @var TimeSeriesEntryArray $entries */
            $entries = $this->getTimeSeriesAndIncludes($from, $to, null, $start, $pageSize);
            if ($entries == null) {
                return null;
            }

            return TypedTimeSeriesEntryArray::fromArray(array_map(function(TimeSeriesEntry $e) { return $e->asTypedEntry($this->className);}, $entries->getArrayCopy()));
        }

        $results = $this->getFromCache($from, $to, null, $start, $pageSize);

        return TypedTimeSeriesEntryArray::fromArray(array_map(function(TimeSeriesEntry $e) { return $e->asTypedEntry($this->className);}, $results->getArrayCopy()));
    }

    /**
     * @param DateTimeInterface|null $timestamp
     * @param T $entry
     * @param string|null $tag
     */
    public function append(?DateTimeInterface $timestamp, mixed $entry, ?string $tag = null): void
    {
        $values = TimeSeriesValuesHelper::getValues(get_class($entry), $entry);
        parent::append($timestamp, $values, $tag);
    }

    public function appendEntry(TypedTimeSeriesEntry $entry): void
    {
        $this->append($entry->getTimestamp(), $entry->getValue(), $entry->getTag());
    }
}
