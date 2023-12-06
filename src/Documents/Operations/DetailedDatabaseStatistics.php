<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\ResultInterface;

class DetailedDatabaseStatistics extends DatabaseStatistics implements ResultInterface
{
    private ?int $countOfIdentities = null;
    private ?int $countOfCompareExchange = null;
    private ?int $countOfCompareExchangeTombstones = null;
    private ?int $countOfTimeSeriesDeletedRanges = null;

    public function getCountOfIdentities(): ?int
    {
        return $this->countOfIdentities;
    }

    public function setCountOfIdentities(?int $countOfIdentities): void
    {
        $this->countOfIdentities = $countOfIdentities;
    }

    public function getCountOfCompareExchange(): ?int
    {
        return $this->countOfCompareExchange;
    }

    public function setCountOfCompareExchange(?int $countOfCompareExchange): void
    {
        $this->countOfCompareExchange = $countOfCompareExchange;
    }

    public function getCountOfCompareExchangeTombstones(): ?int
    {
        return $this->countOfCompareExchangeTombstones;
    }

    public function setCountOfCompareExchangeTombstones(?int $countOfCompareExchangeTombstones): void
    {
        $this->countOfCompareExchangeTombstones = $countOfCompareExchangeTombstones;
    }

    public function getCountOfTimeSeriesDeletedRanges(): ?int
    {
        return $this->countOfTimeSeriesDeletedRanges;
    }

    public function setCountOfTimeSeriesDeletedRanges(?int $countOfTimeSeriesDeletedRanges): void
    {
        $this->countOfTimeSeriesDeletedRanges = $countOfTimeSeriesDeletedRanges;
    }
}
