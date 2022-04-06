<?php

namespace RavenDB\Exceptions;

use RavenDB\Http\Topology;
use Throwable;

class AllTopologyNodesDownException extends \RuntimeException
{
    private ?Topology $failedTopology = null;

    public function __construct($message = "", ?Topology $failedTopology = null, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->failedTopology = $failedTopology;
    }

    public function getFailedTopology(): ?Topology
    {
        return $this->failedTopology;
    }
}
