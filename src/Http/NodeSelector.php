<?php

namespace RavenDB\Http;

class NodeSelector
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

    public function setTopology(Topology $topology): void
    {
        $this->topology = $topology;
    }

    public function getPreferredNode(): ServerNode
    {
        return $this->topology->getServerNodes()[0];
    }
}
