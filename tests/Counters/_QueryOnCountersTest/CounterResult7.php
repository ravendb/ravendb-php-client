<?php

namespace tests\RavenDB\Counters\_QueryOnCountersTest;

class CounterResult7
{
    private ?int $downloads = null;
    private ?int $friendsDownloads = null;
    private ?string $name = null;

    public function getDownloads(): ?int
    {
        return $this->downloads;
    }

    public function setDownloads(?int $downloads): void
    {
        $this->downloads = $downloads;
    }

    public function getFriendsDownloads(): ?int
    {
        return $this->friendsDownloads;
    }

    public function setFriendsDownloads(?int $friendsDownloads): void
    {
        $this->friendsDownloads = $friendsDownloads;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
