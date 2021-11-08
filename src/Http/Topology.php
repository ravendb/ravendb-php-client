<?php

namespace RavenDB\Http;

class Topology
{
    private int $eTag = -1;
    private ServerNodeArray $serverNodes;

    public function __construct()
    {
        $this->serverNodes = new ServerNodeArray();
    }

    public function getETag(): int
    {
        return $this->eTag;
    }

    public function setETag(int $eTag): void
    {
        $this->eTag = $eTag;
    }

    public function getServerNodes(): ServerNodeArray
    {
        return $this->serverNodes;
    }

    public function setServerNodes(ServerNodeArray $serverNodes): void
    {
        $this->serverNodes = $serverNodes;
    }
}
