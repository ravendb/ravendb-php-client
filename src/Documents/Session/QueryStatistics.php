<?php

namespace RavenDB\Documents\Session;

use DateTimeInterface;
use RavenDB\Documents\Queries\QueryResult;

/**
 * Statistics about a raven query.
 * Such as how many records match the query
 */

class QueryStatistics
{
    private bool $isStale = false;
    private int $durationInMs = 0;
    private int $totalResults = 0;
    private int $longTotalResults = 0;
    private int $skippedResults = 0;
    private ?DateTimeInterface $timestamp = null;
    private ?string $indexName = null;
    private ?DateTimeInterface $indexTimestamp = null;
    private ?DateTimeInterface $lastQueryTime = null;
    private ?int $resultEtag = null;
    private ?string $nodeTag = null;

    /**
     * Whether the query returned potentially stale results
     * @return bool true is query result is stale
     */

    public function isStale(): bool
    {
        return $this->isStale;
    }

    /**
     * Whether the query returned potentially stale results
     * @param bool $isStale sets the value
     */
    public function setIsStale(bool $isStale): void
    {
        $this->isStale = $isStale;
    }

    public function getDurationInMs(): int
    {
        return $this->durationInMs;
    }

    public function setDurationInMs(int $durationInMs): void
    {
        $this->durationInMs = $durationInMs;
    }

    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    public function setTotalResults(int $totalResults): void
    {
        $this->totalResults = $totalResults;
    }

    public function getLongTotalResults(): int
    {
        return $this->longTotalResults;
    }

    public function setLongTotalResults(int $longTotalResults): void
    {
        $this->longTotalResults = $longTotalResults;
    }

    public function getSkippedResults(): int
    {
        return $this->skippedResults;
    }

    public function setSkippedResults(int $skippedResults): void
    {
        $this->skippedResults = $skippedResults;
    }

    public function getTimestamp(): ?DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(?DateTimeInterface $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function setIndexName(?string $indexName): void
    {
        $this->indexName = $indexName;
    }

    public function getIndexTimestamp(): ?DateTimeInterface
    {
        return $this->indexTimestamp;
    }

    public function setIndexTimestamp(?DateTimeInterface $indexTimestamp): void
    {
        $this->indexTimestamp = $indexTimestamp;
    }

    public function getLastQueryTime(): ?DateTimeInterface
    {
        return $this->lastQueryTime;
    }

    public function setLastQueryTime(?DateTimeInterface $lastQueryTime): void
    {
        $this->lastQueryTime = $lastQueryTime;
    }

    public function getResultEtag(): ?int
    {
        return $this->resultEtag;
    }

    public function setResultEtag(?int $resultEtag): void
    {
        $this->resultEtag = $resultEtag;
    }

    public function getNodeTag(): ?string
    {
        return $this->nodeTag;
    }

    public function setNodeTag(?string $nodeTag): void
    {
        $this->nodeTag = $nodeTag;
    }


    public function updateQueryStats(QueryResult $qr)
    {
        $this->isStale = $qr->isStale();
        $this->durationInMs = $qr->getDurationInMs();
        $this->totalResults = $qr->getTotalResults();
        $this->longTotalResults = $qr->getLongTotalResults();
        $this->skippedResults = $qr->getSkippedResults();
        $this->timestamp = $qr->getIndexTimestamp();
        $this->indexName = $qr->getIndexName();
        $this->indexTimestamp = $qr->getIndexTimestamp();
        $this->lastQueryTime = $qr->getLastQueryTime();
        $this->resultEtag = $qr->getResultEtag();
        $this->nodeTag = $qr->getNodeTag();
    }
}
