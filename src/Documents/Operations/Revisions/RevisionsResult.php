<?php

namespace RavenDB\Documents\Operations\Revisions;

class RevisionsResult
{
    private ?array $results = null;
    private ?int $totalResults = null;

    public function getResults(): ?array
    {
        return $this->results;
    }

    public function setResults(?array $results): void
    {
        $this->results = $results;
    }

    public function getTotalResults(): ?int
    {
        return $this->totalResults;
    }

    public function setTotalResults(?int $totalResults): void
    {
        $this->totalResults = $totalResults;
    }
}
