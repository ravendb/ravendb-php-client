<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesRawQueryTest;

class NestedClass
{
    private ?Event $event = null;
    private ?float $accuracy = null;

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    public function getAccuracy(): ?float
    {
        return $this->accuracy;
    }

    public function setAccuracy(?float $accuracy): void
    {
        $this->accuracy = $accuracy;
    }
}
