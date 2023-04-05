<?php

namespace RavenDB\Documents\Queries\Spatial;

use Closure;

abstract class DynamicSpatialField
{
    private ?float $roundFactor = null;

    abstract public function toField(Closure $ensureValidFieldName): string;

    public function getRoundFactor(): ?float
    {
        return $this->roundFactor;
    }

    public function roundTo(float $factor): DynamicSpatialField
    {
        $this->roundFactor = $factor;
        return $this;
    }
}
