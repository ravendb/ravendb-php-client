<?php

namespace RavenDB\Http;

class ClusterTopologyResponse implements ResultInterface
{
    private ?string $leader = null;

    private string $nodeTag = '';

    private ?ClusterTopology $topology = null;

    private int $etag;

    private NodeStatusMap $status;

    public function __construct()
    {
        $this->status = new NodeStatusMap();
    }

    public function getStatus(): NodeStatusMap
    {
        return $this->status;
    }

    public function setStatus(NodeStatusMap $status): void
    {
        $this->status = $status;
    }

    public function getLeader(): ?string
    {
        return $this->leader;
    }

    public function setLeader(?string $leader): void
    {
        $this->leader = $leader;
    }

    public function getNodeTag(): string
    {
        return $this->nodeTag;
    }

    public function setNodeTag(string $nodeTag): void
    {
        $this->nodeTag = $nodeTag;
    }

    public function getTopology(): ?ClusterTopology
    {
        return $this->topology;
    }

    public function setTopology(?ClusterTopology $topology): void
    {
        $this->topology = $topology;
    }

    public function getEtag(): int
    {
        return $this->etag;
    }

    public function setEtag(int $etag): void
    {
        $this->etag = $etag;
    }
}
