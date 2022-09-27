<?php

namespace RavenDB\Documents\Queries\Highlighting;

use RavenDB\Documents\Queries\QueryResult;

class QueryHighlightings
{
    private ?HighlightingsList $highlightings = null;

    public function __construct()
    {
        $this->highlightings = new HighlightingsList();
    }

    public function add(?string $fieldName): Highlightings
    {
        $fieldHighlightings = new Highlightings($fieldName);
        $this->highlightings->append($fieldHighlightings);
        return $fieldHighlightings;
    }

    public function update(QueryResult $queryResult): void
    {
        foreach ($this->highlightings as $fieldHighlightings) {
            $fieldHighlightings->update($queryResult->getHighlightings());
        }
    }
}
