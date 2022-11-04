<?php

namespace RavenDB\Http;

// !status: DONE
class Topology
{
    private int $eTag = -1;
    private ServerNodeArray $nodes;

    public function __construct()
    {
        $this->nodes = new ServerNodeArray();
    }

    public function getETag(): int
    {
        return $this->eTag;
    }

    public function setETag(int $eTag): void
    {
        $this->eTag = $eTag;
    }

    public function getNodes(): ServerNodeArray
    {
        return $this->nodes;
    }

    /**
     * @param ServerNodeArray|array $nodes
     */
    public function setNodes($nodes): void
    {
        $this->nodes = is_array($nodes) ? ServerNodeArray::fromArray($nodes) : $nodes;
    }
}
