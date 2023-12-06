<?php

namespace RavenDB\Documents\Queries\Facets;


interface FacetOperationsInterface
{
    public function withDisplayName(string $displayName): FacetOperationsInterface;

    public function withOptions(FacetOptions $options): FacetOperationsInterface;

    public function sumOn(string $path, ?string $displayName = null): FacetOperationsInterface;
    public function minOn(string $path, ?string $displayName = null): FacetOperationsInterface;
    public function maxOn(string $path, ?string $displayName = null): FacetOperationsInterface;
    public function averageOn(string $path, ?string $displayName = null): FacetOperationsInterface;

    //TBD expr overloads with expression
}
