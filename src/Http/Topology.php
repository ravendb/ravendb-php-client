<?php

namespace RavenDB\Http;

class Topology
{
    private ?int $eTag = null;
    private ?ServerNodeList $nodes = null;

    public function __construct()
    {
        $this->nodes = new ServerNodeList();
    }

    public function getETag(): ?int
    {
        return $this->eTag;
    }

    public function setETag(?int $eTag): void
    {
        $this->eTag = $eTag;
    }

    public function getNodes(): ?ServerNodeList
    {
        return $this->nodes;
    }

    /**
     * @param ServerNodeList|array|null $nodes
     */
    public function setNodes(ServerNodeList|array|null $nodes): void
    {
        $this->nodes = is_array($nodes) ? ServerNodeList::fromArray($nodes) : $nodes;
    }
}
