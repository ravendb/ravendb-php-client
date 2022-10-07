<?php

namespace tests\RavenDB\Test\Issues\RavenDB_6558Test;

use RavenDB\Type\StringList;

class SearchResults
{
    private ?SearchableInterface $result = null;
    private ?StringList $highlights = null;
    private ?string $title = null;

    public function getResult(): ?SearchableInterface
    {
        return $this->result;
    }

    public function setResult(?SearchableInterface $result): void
    {
        $this->result = $result;
    }

    public function getHighlights(): ?StringList
    {
        return $this->highlights;
    }

    public function setHighlights(?StringList $highlights): void
    {
        $this->highlights = $highlights;
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
