<?php

namespace RavenDB\Documents\Queries\Suggestions;

class SuggestionBase
{
    private ?string $field = null;
    private ?string $displayField = null;
    private ?SuggestionOptions $options = null;

    protected function __construct(?string $field)
    {
        $this->field = $field;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setField(?string $field): void
    {
        $this->field = $field;
    }

    public function getDisplayField(): ?string
    {
        return $this->displayField;
    }

    public function setDisplayField(?string $displayField): void
    {
        $this->displayField = $displayField;
    }

    public function getOptions(): ?SuggestionOptions
    {
        return $this->options;
    }

    public function setOptions(?SuggestionOptions $options): void
    {
        $this->options = $options;
    }
}
