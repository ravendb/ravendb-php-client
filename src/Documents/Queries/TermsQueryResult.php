<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Type\StringSet;

class TermsQueryResult
{
    private ?StringSet $terms = null;
    private int $resultEtag = 0;
    private ?string $indexName = null;

    public function getTerms(): ?StringSet
    {
        return $this->terms;
    }

    public function setTerms(?StringSet $terms): void
    {
        $this->terms = $terms;
    }

    public function getResultEtag(): int
    {
        return $this->resultEtag;
    }

    public function setResultEtag(int $resultEtag): void
    {
        $this->resultEtag = $resultEtag;
    }

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function setIndexName(?string $indexName): void
    {
        $this->indexName = $indexName;
    }
}
