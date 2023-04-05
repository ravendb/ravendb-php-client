<?php

namespace tests\RavenDB\Test\Client\Spatial\_SimonBartlettTest;

use Symfony\Component\Serializer\Annotation\SerializedName;

class GeoDocument
{
    #[SerializedName("WKT")]
    private ?string $wkt;

    public function getWkt(): ?string
    {
        return $this->wkt;
    }

    public function setWkt(?string $wkt): void
    {
        $this->wkt = $wkt;
    }
}
