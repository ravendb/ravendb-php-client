<?php

namespace tests\RavenDB\Test\Client\Indexing\Counters\_BasicCountersIndexes_JavaScript;

class AverageHeartRate_WithLoadResult
{
    private ?float $heartBeat = null;
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
