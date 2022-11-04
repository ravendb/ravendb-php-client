<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Documents\Indexes\Spatial\AutoSpatialOptions;

class AutoIndexFieldOptions
{
    private ?FieldStorage $storage = null;
    private ?AutoFieldIndexing $indexing = null;
    private ?AggregationOperation $aggregation = null;
    private ?AutoSpatialOptions $spatial = null;
    private ?GroupByArrayBehavior $groupByArrayBehavior = null;
    private bool $suggestions = false;
    private bool $isNameQuoted = false;

    public function getStorage(): ?FieldStorage
    {
        return $this->storage;
    }

    public function setStorage(?FieldStorage $storage): void
    {
        $this->storage = $storage;
    }

    public function getIndexing(): ?AutoFieldIndexing
    {
        return $this->indexing;
    }

    public function setIndexing(?AutoFieldIndexing $indexing): void
    {
        $this->indexing = $indexing;
    }

    public function getAggregation(): ?AggregationOperation
    {
        return $this->aggregation;
    }

    public function setAggregation(?AggregationOperation $aggregation): void
    {
        $this->aggregation = $aggregation;
    }

    public function getSpatial(): ?AutoSpatialOptions
    {
        return $this->spatial;
    }

    public function setSpatial(?AutoSpatialOptions $spatial): void
    {
        $this->spatial = $spatial;
    }

    public function getGroupByArrayBehavior(): ?GroupByArrayBehavior
    {
        return $this->groupByArrayBehavior;
    }

    public function setGroupByArrayBehavior(?GroupByArrayBehavior $groupByArrayBehavior): void
    {
        $this->groupByArrayBehavior = $groupByArrayBehavior;
    }

    public function getSuggestions(): bool
    {
        return $this->suggestions;
    }

    public function setSuggestions(bool $suggestions): void
    {
        $this->suggestions = $suggestions;
    }

    public function isNameQuoted(): bool
    {
        return $this->isNameQuoted;
    }

    public function setIsNameQuoted(bool $isNameQuoted): void
    {
        $this->isNameQuoted = $isNameQuoted;
    }
}
