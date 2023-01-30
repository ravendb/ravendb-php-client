<?php

namespace RavenDB\Documents\Indexes\TimeSeries;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\AbstractIndexDefinitionBuilder;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Exceptions\IllegalStateException;

class TimeSeriesIndexDefinitionBuilder extends AbstractIndexDefinitionBuilder
{
    private ?string $map = null;

    public function __construct(?string $indexName = null)
    {
        parent::__construct($indexName);
    }

    public function getMap(): ?string
    {
        return $this->map;
    }

    public function setMap(?string $map): void
    {
        $this->map = $map;
    }

    protected function newIndexDefinition(): TimeSeriesIndexDefinition
    {
        return new TimeSeriesIndexDefinition();
    }

    public function toIndexDefinition(?DocumentConventions $conventions, bool $validateMap = true): TimeSeriesIndexDefinition
    {
        if ($this->map == null && $validateMap) {
            throw new IllegalStateException("Map is required to generate an index, you cannot create an index without a valid Map property (in index " . $this->indexName . ").");
        }

        return parent::toIndexDefinition($conventions, $validateMap);
    }


    protected function addToIndexDefinition(IndexDefinition $indexDefinition, ?DocumentConventions $conventions): void
    {
        if ($this->map == null) {
            return;
        }

        $indexDefinition->getMaps()->append($this->map);
    }
}
