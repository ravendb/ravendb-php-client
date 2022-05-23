<?php

namespace tests\RavenDB\Test\Issues\RavenDB_13682Test;

class Item
{
    private ?float $lat = null;
    private ?float $lng = null;
    private ?string $name = null;

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function setLat(?float $lat): void
    {
        $this->lat = $lat;
    }

    public function getLng(): ?float
    {
        return $this->lng;
    }

    public function setLng(?float $lng): void
    {
        $this->lng = $lng;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
