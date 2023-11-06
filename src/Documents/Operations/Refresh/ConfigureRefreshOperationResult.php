<?php

namespace RavenDB\Documents\Operations\Refresh;

use RavenDB\Http\ResultInterface;

class ConfigureRefreshOperationResult implements ResultInterface
{
    private ?int $raftCommandIndex = null;

    public function getRaftCommandIndex(): ?int
    {
        return $this->raftCommandIndex;
    }

    public function setRaftCommandIndex(?int $raftCommandIndex): void
    {
        $this->raftCommandIndex = $raftCommandIndex;
    }
}
