<?php

namespace RavenDB\Documents\Queries\Suggestions;

use RavenDB\Type\StringArray;

class SuggestionWithTerms extends SuggestionBase
{
    private ?StringArray $terms;

    public function __construct(?string $field)
    {
        parent::__construct($field);
    }

    public function getTerms(): ?StringArray
    {
        return $this->terms;
    }

    public function setTerms(null|StringArray|array $terms): void
    {
        if (is_array($terms)) {
            $terms = StringArray::fromArray($terms);
        }
        $this->terms = $terms;
    }
}
