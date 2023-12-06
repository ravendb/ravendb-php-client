<?php

namespace RavenDB\Documents\Indexes;

class IndexStatus
{
    private ?string $name = null;
    private ?IndexRunningStatus $status = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getStatus(): ?IndexRunningStatus
    {
        return $this->status;
    }

    public function setStatus(?IndexRunningStatus $status): void
    {
        $this->status = $status;
    }
}
