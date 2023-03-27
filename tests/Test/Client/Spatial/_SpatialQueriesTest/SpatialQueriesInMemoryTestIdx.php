<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialQueriesTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class SpatialQueriesInMemoryTestIdx extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "docs.Listings.Select(listingItem => new {\n" .
            "    classCodes = listingItem.classCodes,\n" .
            "    latitude = listingItem.latitude,\n" .
            "    longitude = listingItem.longitude,\n" .
            "    coordinates = this.CreateSpatialField(((double ? )((double)(listingItem.latitude))), ((double ? )((double)(listingItem.longitude))))\n" .
            "})";
    }
}
