<?php

namespace RavenDB\ServerWide;

class LeaderStamp
{
    private ?int $index = null;
    private ?int $term = null;
    private ?int $leadersTicks = null;

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function setIndex(?int $index): void
    {
        $this->index = $index;
    }

    public function getTerm(): ?int
    {
        return $this->term;
    }

    public function setTerm(?int $term): void
    {
        $this->term = $term;
    }

    public function getLeadersTicks(): ?int
    {
        return $this->leadersTicks;
    }

    public function setLeadersTicks(?int $leadersTicks): void
    {
        $this->leadersTicks = $leadersTicks;
    }
}
