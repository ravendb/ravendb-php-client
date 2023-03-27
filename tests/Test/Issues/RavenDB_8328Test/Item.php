<?php

namespace tests\RavenDB\Test\Issues\RavenDB_8328Test;

class Item
{
    private ?string $id = null;
    private ?string $name = null;
    private ?float $latitude = null;
    private ?float $longitude = null;
    private ?float $latitude2 = null;
    private ?float $longitude2 = null;
    private ?string $shapeWkt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getLatitude2(): ?float
    {
        return $this->latitude2;
    }

    public function setLatitude2(?float $latitude2): void
    {
        $this->latitude2 = $latitude2;
    }

    public function getLongitude2(): ?float
    {
        return $this->longitude2;
    }

    public function setLongitude2(?float $longitude2): void
    {
        $this->longitude2 = $longitude2;
    }

    public function getShapeWkt(): ?string
    {
        return $this->shapeWkt;
    }

    public function setShapeWkt(?string $shapeWkt): void
    {
        $this->shapeWkt = $shapeWkt;
    }
}
