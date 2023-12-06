<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Documents\Indexes\Spatial\SpatialOptions;

use Symfony\Component\Serializer\Annotation\SerializedName;

class IndexFieldOptions
{
    /** @SerializedName ("Storage") */
    private ?FieldStorage $storage = null;

    /** @SerializedName ("Indexing") */
    private ?FieldIndexing $indexing = null;

    /** @SerializedName ("TermVector") */
    private ?FieldTermVector $termVector = null;

    /** @SerializedName ("Spatial") */
    private ?SpatialOptions $spatial = null;

    /** @SerializedName ("Analyzer") */
    private ?string $analyzer = null;

    /** @SerializedName ("Suggestions") */
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
