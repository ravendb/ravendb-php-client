<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use DateTime;
use RavenDB\Type\Duration;

class NextBackup
{
    private ?Duration $timeSpan = null;
    private ?DateTime $dateTime = null;
    private bool $isFull = false;

    public function getTimeSpan(): ?Duration
    {
        return $this->timeSpan;
    }

    public function setTimeSpan(?Duration $timeSpan): void
    {
        $this->timeSpan = $timeSpan;
    }

    public function getDateTime(): ?DateTime
    {
        return $this->dateTime;
    }

    public function setDateTime(?DateTime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

    public function isFull(): bool
    {
        return $this->isFull;
    }

    public function setIsFull(bool $isFull): void
    {
        $this->isFull = $isFull;
    }
}
