<?php

namespace RavenDB\Documents\Queries\Explanation;

use RavenDB\Documents\Queries\QueryResult;

class Explanations
{
    /** @var array<array<string>> */
    private array $explanations = [];

    /**
     * @param string|null $key
     * @return array<string>|array<array<string>>
     */
    public function getExplanations(?string $key = null): ?array
    {
        if (empty($key)) {
            return $this->explanations;
        }

        return array_key_exists($key, $this->explanations) ? $this->explanations[$key] : null;
    }

    /**
     * @param array<array<string>> $explanations
     */
    public function setExplanations(array $explanations): void
    {
        $this->explanations = $explanations;
    }

    public function update(QueryResult $queryResult): void
    {
        $this->explanations = $queryResult->getExplanations();
    }
}
