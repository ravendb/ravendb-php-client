<?php

namespace RavenDB\Documents\Indexes;

class RollingIndex
{
    private RollingIndexDeploymentArray $activeDeployments;

    private ?int $raftCommandIndex = null;

    public function __construct()
    {
        $this->activeDeployments = new RollingIndexDeploymentArray();
    }

    public function getActiveDeployments(): RollingIndexDeploymentArray
    {
        return $this->activeDeployments;
    }

    public function setActiveDeployments(RollingIndexDeploymentArray $activeDeployments): void
    {
        $this->activeDeployments = $activeDeployments;
    }

    public function getRaftCommandIndex(): ?int
    {
        return $this->raftCommandIndex;
    }

    public function setRaftCommandIndex(?int $raftCommandIndex): void
    {
        $this->raftCommandIndex = $raftCommandIndex;
    }
}
