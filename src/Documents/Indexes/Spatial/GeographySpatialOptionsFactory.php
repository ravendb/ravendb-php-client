<?php

namespace RavenDB\Documents\Indexes\Spatial;

class GeographySpatialOptionsFactory
{
        /**
         * Defines a Geohash Prefix Tree index using a default Max Tree Level {@link SpatialOptions}
         * @param null|SpatialUnits $circleRadiusUnits Units to set
         * @return SpatialOptions Spatial options
         */
        public function defaultOptions(?SpatialUnits $circleRadiusUnits = null): SpatialOptions
        {
            if ($circleRadiusUnits == null) {
                $circleRadiusUnits = SpatialUnits::kilometers();
            }
            return self::geohashPrefixTreeIndex(0, $circleRadiusUnits);
        }

        public function boundingBoxIndex(?SpatialUnits $circleRadiusUnits = null): SpatialOptions
        {
            if ($circleRadiusUnits == null) {
                $circleRadiusUnits = SpatialUnits::kilometers();
            }

            $ops = new SpatialOptions();
            $ops->setType(SpatialFieldType::geography());
            $ops->setStrategy(SpatialSearchStrategy::boundingBox());
            $ops->setUnits($circleRadiusUnits);
            return $ops;
        }

        public function geohashPrefixTreeIndex(int $maxTreeLevel, ?SpatialUnits $circleRadiusUnits = null): SpatialOptions
        {
            if ($circleRadiusUnits == null) {
                $circleRadiusUnits = SpatialUnits::kilometers();
            }

            if ($maxTreeLevel == 0)
                $maxTreeLevel = SpatialOptions::DEFAULT_GEOHASH_LEVEL;

            $opts = new SpatialOptions();
            $opts->setType(SpatialFieldType::geography());
            $opts->setMaxTreeLevel($maxTreeLevel);
            $opts->setStrategy(SpatialSearchStrategy::geohashPrefixTree());
            $opts->setUnits($circleRadiusUnits);
            return $opts;
        }

        public function quadPrefixTreeIndex(int $maxTreeLevel, ?SpatialUnits $circleRadiusUnits = null): SpatialOptions
        {
            if ($circleRadiusUnits == null) {
                $circleRadiusUnits = SpatialUnits::kilometers();
            }

            if ($maxTreeLevel == 0)
                $maxTreeLevel = SpatialOptions::DEFAULT_QUAD_TREE_LEVEL;

            $opts = new SpatialOptions();
            $opts->setType(SpatialFieldType::geography());
            $opts->setMaxTreeLevel($maxTreeLevel);
            $opts->setStrategy(SpatialSearchStrategy::quadPrefixTree());
            $opts->setUnits($circleRadiusUnits);
            return $opts;
        }
}
