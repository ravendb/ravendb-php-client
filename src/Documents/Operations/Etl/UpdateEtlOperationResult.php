<?php

namespace RavenDB\Documents\Operations\Etl;

class UpdateEtlOperationResult
{
    private ?int $raftCommandIndex = null;
    private ?int $taskId = null;

    public function getRaftCommandIndex(): ?int
    {
        return $this->raftCommandIndex;
    }

    public function setRaftCommandIndex(?int $raftCommandIndex): void
    {
        $this->raftCommandIndex = $raftCommandIndex;
    }

    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    public function setTaskId(?int $taskId): void
    {
        $this->taskId = $taskId;
    }

}
