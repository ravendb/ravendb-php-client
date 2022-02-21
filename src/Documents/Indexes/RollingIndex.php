<?php

namespace RavenDB\Documents\Indexes;

// !status: DONE
class RollingIndex
{
    private RollingIndexDeploymentArray $activeDeployments;

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
}
