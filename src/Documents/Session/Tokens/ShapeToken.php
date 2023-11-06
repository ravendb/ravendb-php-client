<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Documents\Indexes\Spatial\SpatialUnits;
use RavenDB\Utils\StringBuilder;

class ShapeToken extends QueryToken
{
    private string $shape;

    private function __construct(string $shape)
    {
        $this->shape = $shape;
    }

    public static function circle(string $radiusParameterName, string $latitudeParameterName, string $longitudeParameterName, ?SpatialUnits $radiusUnits): ShapeToken
    {
        if ($radiusUnits == null) {
            return new ShapeToken("spatial.circle($" . $radiusParameterName . ", $" . $latitudeParameterName . ", $" . $longitudeParameterName . ")");
        }

        if ($radiusUnits->isKilometers()) {
            return new ShapeToken("spatial.circle($" . $radiusParameterName . ", $" . $latitudeParameterName . ", $" . $longitudeParameterName . ", 'Kilometers')");
        }
        return new ShapeToken("spatial.circle($" . $radiusParameterName . ", $" . $latitudeParameterName . ", $" . $longitudeParameterName . ", 'Miles')");
    }

    public static function wkt(string $shapeWktParameterName, ?SpatialUnits $units): ShapeToken
    {
        if ($units == null) {
            return new ShapeToken("spatial.wkt($" . $shapeWktParameterName . ")");
        }

        if ($units->isKilometers()) {
            return new ShapeToken("spatial.wkt($" . $shapeWktParameterName . ", 'Kilometers')");
        }
        return new ShapeToken("spatial.wkt($" . $shapeWktParameterName . ", 'Miles')");
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append($this->shape);
    }
}
