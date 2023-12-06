<?php

namespace RavenDB\Documents\Session;

use RavenDB\Http\Topology;
use RavenDB\Primitives\EventArgs;

class TopologyUpdatedEventArgs extends EventArgs
{
    private Topology $topology;

    public function __construct(Topology $topology)
    {
        $this->topology = $topology;
    }

    public function getTopology(): Topology
    {
        return $this->topology;
    }
}
