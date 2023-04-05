<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialTest;

class MyDocument
{
    private ?string $id = null;
    private ?MyDocumentItemList $items = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getItems(): ?MyDocumentItemList
    {
        return $this->items;
    }

    public function setItems(null|MyDocumentItemList|array $items): void
    {
        $this->items = is_array($items) ? MyDocumentItemList::fromArray($items) : $items;
    }
}
