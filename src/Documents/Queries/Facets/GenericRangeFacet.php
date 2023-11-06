<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Documents\Session\Tokens\FacetToken;

class GenericRangeFacet extends FacetBase
{
      private ?FacetBase $parent = null;

    private RangeBuilderArray $ranges;

    public function __construct(?FacetBase $parent = null)
    {
        parent::__construct();

        $this->parent = $parent;
        $this->ranges = new RangeBuilderArray();
    }

    public static function parse(RangeBuilder $rangeBuilder, $addQueryParameter): string
    {
        return $rangeBuilder->getStringRepresentation($addQueryParameter);
    }

    public function getRanges(): RangeBuilderArray
    {
        return $this->ranges;
    }

    public function setRanges(RangeBuilderArray $ranges): void
    {
        $this->ranges = $ranges;
    }

    public function toFacetToken($addQueryParameter): FacetToken
    {
        if ($this->parent != null) {
            return $this->parent->toFacetToken($addQueryParameter);
        }

        return FacetToken::create($this, $addQueryParameter);
    }
}
