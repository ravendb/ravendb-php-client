<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Documents\Session\Tokens\FacetToken;
use RavenDB\Type\StringArray;

use Symfony\Component\Serializer\Annotation\SerializedName;

class RangeFacet extends FacetBase
{
    private ?FacetBase $parent = null;

    /**
     * @SerializedName("Ranges")
     */
    private StringArray $ranges;

    public function __construct(?FacetBase $parent = null)
    {
        $this->parent = $parent;
        $ranges = new StringArray();
    }

    public function getRanges(): StringArray
    {
        return $this->ranges;
    }

    /**
     * @param StringArray|array $ranges
     */
    public function setRanges($ranges): void
    {
        if (is_array($ranges)) {
            $ranges = StringArray::fromArray($ranges);
        }
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
