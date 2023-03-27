<?php

namespace tests\RavenDB\Test\Client\Spatial\_SimonBartlettTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\Spatial\SpatialOptions;
use RavenDB\Documents\Indexes\Spatial\SpatialSearchStrategy;

class GeoIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "docs.GeoDocuments.Select(doc => new {\n" .
            "    WKT = this.CreateSpatialField(doc.WKT)\n" .
            "})";

        $spatialOptions = new SpatialOptions();
        $spatialOptions->setStrategy(SpatialSearchStrategy::geohashPrefixTree());

        $this->spatialOptionsStrings["WKT"] = $spatialOptions;
    }
}
