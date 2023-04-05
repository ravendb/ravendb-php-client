<?php

namespace tests\RavenDB\Test\Client\Spatial\_BoundingBoxIndexTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class BBoxIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "docs.SpatialDocs.Select(doc => new {\n" .
            "    shape = this.CreateSpatialField(doc.shape)\n" .
            "})";

        $this->spatial("shape", function ($x) {
            return $x->cartesian()->boundingBoxIndex();
        });
    }
}
