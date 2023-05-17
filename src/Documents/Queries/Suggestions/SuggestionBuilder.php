<?php

namespace RavenDB\Documents\Queries\Suggestions;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Type\StringArray;

class SuggestionBuilder implements SuggestionBuilderInterface, SuggestionOperationsInterface
{
    private ?SuggestionWithTerm $term = null;
    private ?SuggestionWithTerms $terms = null;

    public function withDisplayName(?string $displayName): SuggestionOperationsInterface
    {
        $this->getSuggestion()->setDisplayField($displayName);

        return $this;
    }

    public function byField(?string $fieldName, null|string|StringArray|array $terms): SuggestionOperationsInterface
    {
        if (is_string($terms)) {
            return $this->byFieldWithTerm($fieldName, $terms);
        }

        return $this->byFieldWithTerms($fieldName, $terms);
    }

    private function byFieldWithTerm(?string $fieldName, ?string $term): SuggestionOperationsInterface
    {
        if ($fieldName == null) {
            throw new IllegalArgumentException("fieldName cannot be null");
        }

        if ($term == null) {
            throw new IllegalArgumentException("term cannot be null");
        }

        $this->term = new SuggestionWithTerm($fieldName);
        $this->term->setTerm($term);

        return $this;
    }

    private function byFieldWithTerms(?string $fieldName, null|string|StringArray|array $terms): SuggestionOperationsInterface
    {
        if ($fieldName == null) {
            throw new IllegalArgumentException("fieldName cannot be null");
        }

        if ($terms == null) {
            throw new IllegalArgumentException("terms cannot be null");
        }

        if (is_array($terms)) {
            $terms = StringArray::fromArray($terms);
        }

        if ($terms->isEmpty()) {
            throw new IllegalArgumentException("Terms cannot be an empty collection.");
        }

        $this->terms = new SuggestionWithTerms($fieldName);
        $this->terms->setTerms($terms);

        return $this;
    }

    public function withOptions(?SuggestionOptions $options): SuggestionOperationsInterface
    {
        $this->getSuggestion()->setOptions($options);

        return $this;
    }

    public function & getSuggestion(): SuggestionBase
    {
        if ($this->term != null) {
            return $this->term;
        }

        return $this->terms;
    }
}
