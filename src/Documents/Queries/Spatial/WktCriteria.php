<?php

namespace RavenDB\Documents\Queries\Spatial;

use Closure;
use RavenDB\Documents\Indexes\Spatial\SpatialRelation;
use RavenDB\Documents\Indexes\Spatial\SpatialUnits;
use RavenDB\Documents\Session\Tokens\ShapeToken;

class WktCriteria extends SpatialCriteria
{
    private ?string $shapeWkt = null;
    private ?SpatialUnits $radiusUnits = null;

    public function __construct(?string $shapeWkt, ?SpatialRelation $relation, ?SpatialUnits $radiusUnits, float $distanceErrorPct)
    {
        parent::__construct($relation, $distanceErrorPct);
        $this->shapeWkt = $shapeWkt;
        $this->radiusUnits = $radiusUnits;
    }

    protected function getShapeToken(Closure $addQueryParameter): ShapeToken
    {
        return ShapeToken::wkt($addQueryParameter($this->shapeWkt), $this->radiusUnits);
    }
}
