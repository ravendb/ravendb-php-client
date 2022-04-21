<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Documents\Indexes\Spatial\SpatialOptions;

// !status: DONE
class IndexFieldOptions
{
    private ?FieldStorage $storage = null;
    private ?FieldIndexing $indexing = null;
    private ?FieldTermVector $termVector = null;
    private ?SpatialOptions $spatial = null;
    private ?string $analyzer = null;
    private bool $suggestions = false;

    public function getStorage(): ?FieldStorage
    {
        return $this->storage;
    }

    public function setStorage(?FieldStorage $storage): void
    {
        $this->storage = $storage;
    }

    public function getIndexing(): ?FieldIndexing
    {
        return $this->indexing;
    }

    public function setIndexing(?FieldIndexing $indexing): void
    {
        $this->indexing = $indexing;
    }

    public function getTermVector(): ?FieldTermVector
    {
        return $this->termVector;
    }

    public function setTermVector(?FieldTermVector $termVector): void
    {
        $this->termVector = $termVector;
    }

    public function getSpatial(): ?SpatialOptions
    {
        return $this->spatial;
    }

    public function setSpatial(?SpatialOptions $spatial): void
    {
        $this->spatial = $spatial;
    }

    public function getAnalyzer(): ?string
    {
        return $this->analyzer;
    }

    public function setAnalyzer(?string $analyzer): void
    {
        $this->analyzer = $analyzer;
    }

    public function isSuggestions(): bool
    {
        return $this->suggestions;
    }

    public function setSuggestions(bool $suggestions): void
    {
        $this->suggestions = $suggestions;
    }
}
