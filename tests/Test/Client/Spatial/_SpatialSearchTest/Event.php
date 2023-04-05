<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialSearchTest;

use DateTime;

class Event
{
    private ?string $venue = null;
    private ?float $latitude = null;
    private ?float $longitude = null;
    private ?DateTime $date = null;
    private ?int $capacity = null;

    public function __construct(?string $venue, ?float $latitude, float $longitude, ?DateTime $date = null, ?int $capacity = null)
    {
        $this->venue = $venue;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->date = $date;
        $this->capacity = $capacity;
    }

    public function getVenue(): ?string
    {
        return $this->venue;
    }

    public function setVenue(?string $venue): void
    {
        $this->venue = $venue;
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

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(?DateTime $date): void
    {
        $this->date = $date;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): void
    {
        $this->capacity = $capacity;
    }
}
