<?php

namespace RavenDB\Json;

class JsonArrayResult
{
    private ?array $results = null;

    public function getResults(): ?array
    {
        return $this->results;
    }

    public function setResults(?array $results): void
    {
        $this->results = $results;
    }
}
