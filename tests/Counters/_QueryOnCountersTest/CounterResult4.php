<?php

namespace tests\RavenDB\Counters\_QueryOnCountersTest;

class CounterResult4
{
    private ?int $downloads = null;
    private ?string $name = null;
    private ?array $likes = null;

    public function getDownloads(): ?int
    {
        return $this->downloads;
    }

    public function setDownloads(?int $downloads): void
    {
        $this->downloads = $downloads;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getLikes(): ?array
    {
        return $this->likes;
    }

    public function setLikes(?array $likes): void
    {
        $this->likes = $likes;
    }
}
