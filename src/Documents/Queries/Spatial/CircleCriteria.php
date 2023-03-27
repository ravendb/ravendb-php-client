<?php

namespace RavenDB\Documents\Queries\Spatial;

use Closure;
use RavenDB\Documents\Indexes\Spatial\SpatialRelation;
use RavenDB\Documents\Indexes\Spatial\SpatialUnits;
use RavenDB\Documents\Session\Tokens\ShapeToken;

class CircleCriteria extends SpatialCriteria
{
    private ?float $radius = null;
    private ?float $latitude = null;
    private ?float $longitude = null;
    private ?SpatialUnits $radiusUnits = null;

    public function __construct(float $radius, float $latitude, float $longitude, ?SpatialUnits $radiusUnits, SpatialRelation $relation, float $distErrorPercent) {
        parent::__construct($relation, $distErrorPercent);

        $this->radius = $radius;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->radiusUnits = $radiusUnits;
    }

    protected function getShapeToken(Closure $addQueryParameter): ShapeToken
    {
        return ShapeToken::circle($addQueryParameter($this->radius), $addQueryParameter($this->latitude),
                $addQueryParameter($this->longitude), $this->radiusUnits);
    }
}
