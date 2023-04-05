<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialSearchTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldIndexing;

class SpatialIdx extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "docs.Events.Select(e => new {\n" .
            "    capacity = e.capacity,\n" .
            "    venue = e.venue,\n" .
            "    date = e.date,\n" .
            "    coordinates = this.CreateSpatialField(((double ? ) e.latitude), ((double ? ) e.longitude))\n" .
            "})";

        $this->index("venue", FieldIndexing::search());
    }
}
