<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use DateTime;

class RunningBackup
{
    private ?DateTime $startTime = null;
    private bool $isFull = false;
    private ?int $runningBackupTaskId = null;

    public function getStartTime(): ?DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(?DateTime $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function isFull(): bool
    {
        return $this->isFull;
    }

    public function setIsFull(bool $isFull): void
    {
        $this->isFull = $isFull;
    }

    public function getRunningBackupTaskId(): ?int
    {
        return $this->runningBackupTaskId;
    }

    public function setRunningBackupTaskId(?int $runningBackupTaskId): void
    {
        $this->runningBackupTaskId = $runningBackupTaskId;
    }
}
