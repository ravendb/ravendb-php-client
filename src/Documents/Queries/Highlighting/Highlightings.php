<?php

namespace RavenDB\Documents\Queries\Highlighting;

class Highlightings
{
    /** @var array<array<string>> */
    private array $highlightings;
    private ?string $fieldName = null;

    public function __construct(?string $fieldName = null)
    {
        $this->fieldName = $fieldName;
        $this->highlightings = [];
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function getResultIndents(): array
    {
        return array_keys($this->highlightings);
    }

    /**
     * @param ?string $key  The document id, or the map/reduce key field.
     * @return array<string> Returns the list of document's field highlighting fragments.
     */
    public function getFragments(?string $key): array
    {
        $result = array_key_exists($key, $this->highlightings) ? $this->highlightings[$key] : null;
        if ($result == null) {
            return [];
        }
        return $result;
    }

    /**
     * @param array<array<array<string>>> $highlightings
     */
    public function update(?array $highlightings): void
    {
        $this->highlightings = [];

        if ($highlightings == null || !array_key_exists($this->getFieldName(), $highlightings)) {
            return;
        }

        $result = $highlightings[$this->getFieldName()];
        foreach ($result as $key => $value) {
            $this->highlightings[$key] = $value;
        }
    }
}
