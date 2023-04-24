<?php

namespace tests\RavenDB\Test\Client\Indexing\Counters\_BasicCountersIndexes_JavaScript;

class MyMultiMapCounterIndexResult
{
    private ?float $heartBeat = null;
    private ?string $name = null;
    private ?string $user = null;

    public function getHeartBeat(): ?float
    {
        return $this->heartBeat;
    }

    public function setHeartBeat(?float $heartBeat): void
    {
        $this->heartBeat = $heartBeat;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
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
