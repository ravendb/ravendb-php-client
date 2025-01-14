<?php

namespace RavenDB\ServerWide\Operations;

class ConfigureRevisionsForConflictsResult
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
