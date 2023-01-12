<?php

namespace RavenDB\Documents\Operations\TimeSeries;

class ConfigureTimeSeriesOperationResult
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
