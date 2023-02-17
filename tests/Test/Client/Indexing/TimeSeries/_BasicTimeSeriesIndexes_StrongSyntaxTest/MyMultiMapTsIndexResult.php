<?php

namespace tests\RavenDB\Test\Client\Indexing\TimeSeries\_BasicTimeSeriesIndexes_StrongSyntaxTest;

use DateTime;

class MyMultiMapTsIndexResult
{
    private ?float $heartBeat = null;
    private ?DateTime $date = null;
    private ?string $user = null;

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

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(?string $user): void
    {
        $this->user = $user;
    }
}
