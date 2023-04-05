<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialQueriesTest;

class Listing
{
    private ?string $classCodes = null;
    private ?int $latitude = null;
    private ?int $longitude = null;

    public function getClassCodes(): ?string
    {
        return $this->classCodes;
    }

    public function setClassCodes(?string $classCodes): void
    {
        $this->classCodes = $classCodes;
    }

    public function getLatitude(): ?int
    {
        return $this->latitude;
    }

    public function setLatitude(?int $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?int
    {
        return $this->longitude;
    }

    public function setLongitude(?int $longitude): void
    {
        $this->longitude = $longitude;
    }
}
