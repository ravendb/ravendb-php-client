<?php

namespace RavenDB\Documents\Indexes\Spatial;

// !status: DONE
class SpatialOptionsFactory
{
  public function geography(): GeographySpatialOptionsFactory
  {
      return new GeographySpatialOptionsFactory();
  }

    public function cartesian(): CartesianSpatialOptionsFactory
    {
        return new CartesianSpatialOptionsFactory();
    }
}
