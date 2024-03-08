<?php

namespace RavenDB\Documents\Operations\Etl\ElasticSearch;

class ElasticSearchIndex
{
    private ?string $indexName = null;
    private ?string $documentIdProperty = null;
    private bool $insertOnlyMode = false;

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function setIndexName(?string $indexName): void
    {
        $this->indexName = $indexName;
    }

    public function getDocumentIdProperty(): ?string
    {
        return $this->documentIdProperty;
    }

    public function setDocumentIdProperty(?string $documentIdProperty): void
    {
        $this->documentIdProperty = $documentIdProperty;
    }

    public function isInsertOnlyMode(): bool
    {
        return $this->insertOnlyMode;
    }

    public function setInsertOnlyMode(bool $insertOnlyMode): void
    {
        $this->insertOnlyMode = $insertOnlyMode;
    }
}
