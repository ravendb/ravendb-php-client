<?php

namespace RavenDB\Http;

class Topology
{
    private int $eTag = -1;
    private ServerNodeList $nodes;

    public function __construct()
    {
        $this->nodes = new ServerNodeList();
    }

    public function getETag(): int
    {
        return $this->eTag;
    }

    public function setETag(int $eTag): void
    {
        $this->eTag = $eTag;
    }

    public function getNodes(): ServerNodeList
    {
        return $this->nodes;
    }

    /**
     * @param ServerNodeList|array $nodes
     */
    public function setNodes($nodes): void
    {
        $this->nodes = is_array($nodes) ? ServerNodeList::fromArray($nodes) : $nodes;
    }
}
