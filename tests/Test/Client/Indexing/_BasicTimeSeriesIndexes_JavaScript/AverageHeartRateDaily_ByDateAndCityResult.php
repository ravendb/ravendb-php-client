<?php

namespace tests\RavenDB\Test\Client\Indexing\_BasicTimeSeriesIndexes_JavaScript;

use DateTime;

class AverageHeartRateDaily_ByDateAndCityResult
{
    private ?float $heartBeat = null;
    private ?DateTime $date = null;
    private ?string $city = null;
    private ?int $count = null;

    public function getHeartBeat(): ?float
    {
        return $this->heartBeat;
    }

    public function setHeartBeat(?float $heartBeat): void
    {
        $this->heartBeat = $heartBeat;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(?DateTime $date): void
    {
        $this->date = $date;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): void
    {
        $this->count = $count;
    }
}
