<?php

namespace RavenDB\Documents\Indexes;

class PutIndexResult
{
    private ?string $index = null;

    private ?int $raftCommandIndex = null;

    public function getIndex(): ?string
    {
        return $this->index;
    }

    public function setIndex(?string $index): void
    {
        $this->index = $index;
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
