<?php

namespace RavenDB\Documents\Session;

use Closure;
use DateTimeInterface;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntryArray;

class SessionDocumentTimeSeries extends SessionTimeSeriesBase implements SessionDocumentTimeSeriesInterface
{
    public function __construct(?InMemoryDocumentSessionOperations $session, $idOrEntity, ?string $name)
    {
        parent::__construct($session, $idOrEntity, $name);
    }

    /**
     * @param DateTimeInterface|null $from
     * @param DateTimeInterface|null $to
     * @param Closure|null $includes
     * @param int $start
     * @param int $pageSize
     *
     * @return TimeSeriesEntryArray|null
     */
    public function get(?DateTimeInterface $from = null, ?DateTimeInterface $to = null, ?Closure $includes = null, int $start = 0, int $pageSize = PHP_INT_MAX): ?TimeSeriesEntryArray
    {
        if ($this->notInCache($from, $to)) {
            return $this->getTimeSeriesAndIncludes($from, $to, $includes, $start, $pageSize);
        }

        $resultsToUser = $this->serveFromCache($from, $to, $start, $pageSize, $includes);
        if ($resultsToUser == null) {
            return null;
        }
        return TimeSeriesEntryArray::fromArray(array_slice($resultsToUser->getArrayCopy(), 0, $pageSize));
    }
}
