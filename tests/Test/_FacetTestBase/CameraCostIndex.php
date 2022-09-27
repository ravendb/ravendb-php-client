<?php

namespace tests\RavenDB\Test\_FacetTestBase;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\IndexDefinition;

class CameraCostIndex extends AbstractIndexCreationTask
{
    public function createIndexDefinition(): IndexDefinition
    {
        $indexDefinition = new IndexDefinition();
        $indexDefinition->setMaps(["from camera in docs.Cameras select new  { camera.manufacturer,\n" .
            "                            camera.model,\n" .
            "                            camera.cost,\n" .
            "                            camera.dateOfListing,\n" .
            "                            camera.megapixels" .
            " }"]);
        $indexDefinition->setName("CameraCost");

        return $indexDefinition;
    }

    public function getIndexName(): string
    {
        return "CameraCost";
    }
}
