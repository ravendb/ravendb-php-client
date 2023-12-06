<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Utils\HashUtils;

class FacetAggregationField
{
    private ?string $name = null;
    private ?string $displayName = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function equals(?object $o): bool
    {
        if ($this == $o) return true;
        if ($o == null || get_class($this) != get_class($o)) return false;
        /** @var FacetAggregationField $that */
        $that = $o;
        return (strcmp($this->name, $that->name)  == 0) &&
            (strcmp($this->displayName, $that->displayName) == 0);
    }

    public function hashCode(): int
    {
        $result = HashUtils::hashCode($this->name);
        return 31 * $result + HashUtils::hashCode($this->displayName);
    }
}
