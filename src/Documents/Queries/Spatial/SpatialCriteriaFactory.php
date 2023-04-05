<?php

namespace RavenDB\Documents\Queries\Spatial;

use RavenDB\Constants\DocumentsIndexingSpatial;
use RavenDB\Documents\Indexes\Spatial\SpatialRelation;
use RavenDB\Documents\Indexes\Spatial\SpatialUnits;

class SpatialCriteriaFactory
{
    private static ?SpatialCriteriaFactory $instance = null;

    public static function instance(): SpatialCriteriaFactory
    {
        if (self::$instance == null) {
            self::$instance = new SpatialCriteriaFactory();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function relatesToShape(?string $shapeWkt, ?SpatialRelation $relation, ?SpatialUnits $units = null, float $distErrorPercent = DocumentsIndexingSpatial::DEFAULT_DISTANCE_ERROR_PCT): SpatialCriteria
    {
        return new WktCriteria($shapeWkt, $relation, $units, $distErrorPercent);
    }

    public function intersects(?string $shapeWkt, ?SpatialUnits $units = null, float $distErrorPercent = DocumentsIndexingSpatial::DEFAULT_DISTANCE_ERROR_PCT): SpatialCriteria
    {
        return $this->relatesToShape($shapeWkt, SpatialRelation::intersects(), $units, $distErrorPercent);
    }

    public function contains(?string $shapeWkt, ?SpatialUnits $units = null, float $distErrorPercent = DocumentsIndexingSpatial::DEFAULT_DISTANCE_ERROR_PCT): SpatialCriteria
    {
        return $this->relatesToShape($shapeWkt, SpatialRelation::contains(), $units, $distErrorPercent);
    }

    public function disjoint(?string $shapeWkt, ?SpatialUnits $units = null, float $distErrorPercent = DocumentsIndexingSpatial::DEFAULT_DISTANCE_ERROR_PCT): SpatialCriteria
    {
        return $this->relatesToShape($shapeWkt, SpatialRelation::disjoint(), $units, $distErrorPercent);
    }

    public function within(?string $shapeWkt, ?SpatialUnits $units = null, float $distErrorPercent = DocumentsIndexingSpatial::DEFAULT_DISTANCE_ERROR_PCT): SpatialCriteria
    {
        return $this->relatesToShape($shapeWkt, SpatialRelation::within(), $units, $distErrorPercent);
    }

    public function withinRadius(float $radius, float $latitude, float $longitude, ?SpatialUnits $radiusUnits = null, float $distErrorPercent = DocumentsIndexingSpatial::DEFAULT_DISTANCE_ERROR_PCT): SpatialCriteria
    {
        return new CircleCriteria($radius, $latitude, $longitude, $radiusUnits, SpatialRelation::within(), $distErrorPercent);
    }
}
