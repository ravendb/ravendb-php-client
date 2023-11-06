<?php

namespace RavenDB\Documents\Indexes\Spatial;

use RavenDB\Type\StringArray;

class AutoSpatialOptions extends SpatialOptions
{
    private ?AutoSpatialMethodType $methodType = null;

    private ?StringArray $methodArguments = null;

    public function getMethodType(): ?AutoSpatialMethodType
    {
        return $this->methodType;
    }

    public function setMethodType(?AutoSpatialMethodType $methodType): void
    {
        $this->methodType = $methodType;
    }

    public function getMethodArguments(): ?StringArray
    {
        return $this->methodArguments;
    }

    public function setMethodArguments(?StringArray $methodArguments): void
    {
        $this->methodArguments = $methodArguments;
    }
}
