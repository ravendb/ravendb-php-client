<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Documents\Session\Tokens\FacetToken;
use RavenDB\Type\StringArray;

use Symfony\Component\Serializer\Annotation\SerializedName;

// !status: DONE
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

    public function setRanges(StringArray $ranges): void
    {
        $this->ranges = $ranges;
    }

    public function toFacetToken($addQueryParameter): FacetToken
    {
        if ($this->parent != null) {
            return $this->parent->toFacetToken($addQueryParameter);
        }

        return FacetToken::createForRangeFacet($this, $addQueryParameter);
    }
}
