<?php

namespace tests\RavenDB\Test\Querying\_HighlightesTest;

use RavenDB\Type\StringList;

class SearchResults
{
    private ?object $result = null;
    private ?StringList $highlights = null;
    private ?string $title = null;

    public function getResult(): ?object
    {
        return $this->result;
    }

    public function setResult(?object $result): void
    {
        $this->result = $result;
    }

    public function getHighlights(): ?StringList
    {
        return $this->highlights;
    }

    public function setHighlights(null|StringList|array $highlights): void
    {
        $this->highlights = is_array($highlights)? StringList::fromArray($highlights) : $highlights;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }
}
