<?php

namespace RavenDB\Documents\Queries\Facets;

use Symfony\Component\Serializer\Annotation\SerializedName;

class FacetSetup
{
    /** @SerializedName ("Id") */
    private ?string $id;

    /** @SerializedName ("Facets") */
    private ?FacetList $facets = null;

    /** @SerializedName ("RangeFacets") */
    private ?RangeFacetList $rangeFacets = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getFacets(): ?FacetList
    {
        return $this->facets;
    }

    /**
     * @param FacetList|array|null $facets
     */
    public function setFacets($facets): void
    {
        if (is_array($facets)) {
            $facets = FacetList::fromArray($facets);
        }
        $this->facets = $facets;
    }

    public function getRangeFacets(): ?RangeFacetList
    {
        return $this->rangeFacets;
    }

    /**
     * @param RangeFacetList|array|null $rangeFacets
     */
    public function setRangeFacets($rangeFacets): void
    {
        if (is_array($rangeFacets)) {
            $rangeFacets = RangeFacetList::fromArray($rangeFacets);
        }
        $this->rangeFacets = $rangeFacets;
    }
}
