<?php

namespace tests\RavenDB\Test\Issues\RavenDB_13682Test;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class SpatialIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "docs.Items.Select(doc => new {\n" .
            "    name = doc.name, \n" .
            "    coordinates = this.CreateSpatialField(doc.lat, doc.lng)\n" .
            "})";
    }
}
