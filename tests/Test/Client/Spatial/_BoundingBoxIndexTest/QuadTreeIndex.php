<?php

namespace tests\RavenDB\Test\Client\Spatial\_BoundingBoxIndexTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\Spatial\SpatialBounds;

class QuadTreeIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "docs.SpatialDocs.Select(doc => new {\n" .
            "    shape = this.CreateSpatialField(doc.shape)\n" .
            "})";

        $this->spatial("shape", function ($x) {
            return $x->cartesian()->quadPrefixTreeIndex(6, new SpatialBounds(0, 0, 16, 16));
        });
    }
}
