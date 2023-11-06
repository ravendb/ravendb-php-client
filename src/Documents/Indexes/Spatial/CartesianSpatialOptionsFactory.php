<?php

namespace RavenDB\Documents\Indexes\Spatial;

use RavenDB\Exceptions\IllegalArgumentException;

class CartesianSpatialOptionsFactory
{
    public function boundingBoxIndex(): SpatialOptions
    {
        $opts = new SpatialOptions();
        $opts->setType(SpatialFieldType::cartesian());
        $opts->setStrategy(SpatialSearchStrategy::boundingBox());
        return $opts;
    }

    public function quadPrefixTreeIndex(int $maxTreeLevel, SpatialBounds $bounds): SpatialOptions
    {
        if ($maxTreeLevel == 0) {
            throw new IllegalArgumentException("maxTreeLevel");
        }

        $opts = new SpatialOptions();
        $opts->setType(SpatialFieldType::cartesian());
        $opts->setMaxTreeLevel($maxTreeLevel);
        $opts->setStrategy(SpatialSearchStrategy::quadPrefixTree());
        $opts->setMinX($bounds->getMinX());
        $opts->setMinY($bounds->getMinY());
        $opts->setMaxX($bounds->getMaxX());
        $opts->setMaxY($bounds->getMaxY());

        return $opts;
    }
}
