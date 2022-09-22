<?php

namespace RavenDB\Documents\Commands;

class ConditionalGetResult
{
    private array $results = [];
    private ?string $changeVector = null;

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function setChangeVector(?string $changeVector): void
    {
        $this->changeVector = $changeVector;
    }
}
