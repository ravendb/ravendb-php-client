<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialTest;

use DateTime;

class MyProjection
{
    private ?string $id = null;
    private ?DateTime $date = null;
    private ?float $latitude = null;
    private ?float $longitude = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(?DateTime $date): void
    {
        $this->date = $date;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(null|float|string $latitude): void
    {
        $this->latitude = $latitude != null ? floatval($latitude) : null;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(null|float|string $longitude): void
    {
        $this->longitude = $longitude != null ? floatval($longitude) : null;
    }
}
