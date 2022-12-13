<?php

namespace RavenDB\Documents\Operations\TimeSeries;

class TimeSeriesStatistics
{
    private ?string $documentId = null;
    private ?TimeSeriesItemDetailArray $timeSeries = null;

    public function __construct()
    {
        $this->timeSeries = new TimeSeriesItemDetailArray();
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function setDocumentId(?string $documentId): void
    {
        $this->documentId = $documentId;
    }

    public function getTimeSeries(): ?TimeSeriesItemDetailArray
    {
        return $this->timeSeries;
    }

    public function setTimeSeries(?TimeSeriesItemDetailArray $timeSeries): void
    {
        $this->timeSeries = $timeSeries;
    }
}
