<?php

namespace tests\RavenDB\Test\Client\Spatial\_BoundingBoxIndexTest;

class SpatialDoc
{
    private ?string $id = null;
    private ?string $shape = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getShape(): ?string
    {
        return $this->shape;
    }

    public function setShape(?string $shape): void
    {
        $this->shape = $shape;
    }
}
