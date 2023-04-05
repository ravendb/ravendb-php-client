<?php

namespace RavenDB\Documents\Queries\Spatial;

use Closure;

class WktField extends DynamicSpatialField
{
    public ?string $wkt = null;

    public function __construct(?string $wkt)
    {
        $this->wkt = $wkt;
    }

    public function toField(Closure $ensureValidFieldName): string
{
        return "spatial.wkt(" . $ensureValidFieldName($this->wkt, false) . ")";
    }
}
