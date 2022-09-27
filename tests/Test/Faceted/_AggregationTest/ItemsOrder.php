<?php

namespace tests\RavenDB\Test\Faceted\_AggregationTest;

use DateTimeInterface;

class ItemsOrder
{
    /** @var array<string>|null  */
    private ?array $items = null;
    private ?DateTimeInterface $at = null;

    public function getItems(): ?array
    {
        return $this->items;
    }

    public function setItems(?array $items): void
    {
        $this->items = $items;
    }

    public function getAt(): ?DateTimeInterface
    {
        return $this->at;
    }

    public function setAt(?DateTimeInterface $at): void
    {
        $this->at = $at;
    }
}
