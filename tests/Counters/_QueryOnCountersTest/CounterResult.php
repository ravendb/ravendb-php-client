<?php

namespace tests\RavenDB\Counters\_QueryOnCountersTest;

class CounterResult
{
    private ?int $downloads = null;
    private ?int $likes = null;
    private ?string $name = null;

    public function getDownloads(): ?int
    {
        return $this->downloads;
    }

    public function setDownloads(?int $downloads): void
    {
        $this->downloads = $downloads;
    }

    public function getLikes(): ?int
    {
        return $this->likes;
    }

    public function setLikes(?int $likes): void
    {
        $this->likes = $likes;
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
