<?php

namespace RavenDB\Documents\Queries\Spatial;

use Closure;

class PointField extends DynamicSpatialField
{
    public ?string $latitude = null;
    public ?string $longitude = null;

    public function __construct(?string $latitude, ?string $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function toField(Closure $ensureValidFieldName): string
    {
        return "spatial.point(" . $ensureValidFieldName($this->latitude, false) . ", " . $ensureValidFieldName($this->longitude, false) . ")";
    }
}
