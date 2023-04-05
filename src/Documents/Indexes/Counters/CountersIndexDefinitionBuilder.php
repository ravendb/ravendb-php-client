<?php

namespace RavenDB\Documents\Indexes\Counters;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\AbstractIndexDefinitionBuilder;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Exceptions\IllegalStateException;

class CountersIndexDefinitionBuilder extends AbstractIndexDefinitionBuilder
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

    protected function newIndexDefinition(): CountersIndexDefinition
    {
        return new CountersIndexDefinition();
    }

    public function toIndexDefinition(?DocumentConventions $conventions, bool $validateMap = true): CountersIndexDefinition
    {
        if ($this->map == null && $validateMap) {
            throw new IllegalStateException("Map is required to generate an index, you cannot create an index without a valid Map property (in index " . $this->indexName . ").");
        }
        /** @var CountersIndexDefinition $cid */
        $cid = parent::toIndexDefinition($conventions, $validateMap);

        return $cid;
    }

//    protected void toIndexDefinition(CountersIndexDefinition indexDefinition, DocumentConventions conventions) {
//        if (map == null) {
//            return;
//        }
//
//        indexDefinition.getMaps().add(map);
//    }
}
