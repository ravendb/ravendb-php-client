<?php

namespace RavenDB\ServerWide\Operations;

class ModifyOngoingTaskResult
{
    private ?int $taskId = null;
    private ?int $raftCommandIndex = null;
    private ?string $responsibleNode = null;

    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    public function setTaskId(?int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getRaftCommandIndex(): ?int
    {
        return $this->raftCommandIndex;
    }

    public function setRaftCommandIndex(?int $raftCommandIndex): void
    {
        $this->raftCommandIndex = $raftCommandIndex;
    }

    public function getResponsibleNode(): ?string
    {
        return $this->responsibleNode;
    }

    public function setResponsibleNode(?string $responsibleNode): void
    {
        $this->responsibleNode = $responsibleNode;
    }
}
