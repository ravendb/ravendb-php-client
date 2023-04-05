<?php

namespace RavenDB\Documents\Queries\Spatial;

use Closure;
use RavenDB\Documents\Indexes\Spatial\SpatialRelation;
use RavenDB\Documents\Session\Tokens\QueryToken;
use RavenDB\Documents\Session\Tokens\ShapeToken;
use RavenDB\Documents\Session\Tokens\WhereOperator;
use RavenDB\Documents\Session\Tokens\WhereOptions;
use RavenDB\Documents\Session\Tokens\WhereToken;
use RavenDB\Exceptions\IllegalArgumentException;

abstract class SpatialCriteria
{
    private SpatialRelation $relation;
    private float $distanceErrorPct;

    protected function __construct(SpatialRelation $relation, float $distanceErrorPct)
    {
        $this->relation = $relation;
        $this->distanceErrorPct = $distanceErrorPct;
    }

    protected abstract function getShapeToken(Closure $addQueryParameter): ShapeToken;

    public function toQueryToken(?string $fieldName, Closure $addQueryParameter): QueryToken
    {
        $shapeToken = $this->getShapeToken($addQueryParameter);

        $whereOperator = null;

        switch ($this->relation) {
            case SpatialRelation::WITHIN:
                $whereOperator = WhereOperator::spatialWithin();
                break;
            case SpatialRelation::CONTAINS:
                $whereOperator = WhereOperator::spatialContains();
                break;
            case SpatialRelation::DISJOINT:
                $whereOperator = WhereOperator::spatialDisjoint();
                break;
            case SpatialRelation::INTERSECTS:
                $whereOperator = WhereOperator::spatialIntersect();
                break;
            default:
                throw new IllegalArgumentException();
        }

        return WhereToken::create($whereOperator, $fieldName, null, new WhereOptions($shapeToken, $this->distanceErrorPct));
    }
}
