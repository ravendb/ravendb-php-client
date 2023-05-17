<?php

namespace RavenDB\Documents\Queries\Suggestions;

class SuggestionWithTerm extends SuggestionBase
{
    private ?string $term = null;

    public function __construct(?string $field)
    {
        parent::__construct($field);
    }

    public function getTerm(): ?string
    {
        return $this->term;
    }

    public function setTerm(?string $term): void
    {
        $this->term = $term;
    }
}
