<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Type\StringList;

class FacetResult
{
    private ?string $name = null;

    private ?FacetValueArray $values = null;

    private ?StringList $remainingTerms = null;

    private ?int $remainingTermsCount = null;

    private ?int $remainingHits = null;

    public function __construct()
    {
        $this->values = new FacetValueArray();
        $this->remainingTerms = new StringList();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getValues(): ?FacetValueArray
    {
        return $this->values;
    }

    public function setValues(?FacetValueArray $values): void
    {
        $this->values = $values;
    }

    public function getRemainingTerms(): ?StringList
    {
        return $this->remainingTerms;
    }

    public function setRemainingTerms(?StringList $remainingTerms): void
    {
        $this->remainingTerms = $remainingTerms;
    }

    public function getRemainingTermsCount(): ?int
    {
        return $this->remainingTermsCount;
    }

    public function setRemainingTermsCount(?int $remainingTermsCount): void
    {
        $this->remainingTermsCount = $remainingTermsCount;
    }

    public function getRemainingHits(): ?int
    {
        return $this->remainingHits;
    }

    public function setRemainingHits(?int $remainingHits): void
    {
        $this->remainingHits = $remainingHits;
    }
}
