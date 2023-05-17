<?php

namespace RavenDB\Documents\Session\Operations;

class DocumentRevisionsCount
{
    private ?int $revisionsCount = null;

    public function getRevisionsCount(): ?int
    {
        return $this->revisionsCount;
    }

    public function setRevisionsCount(?int $revisionsCount): void
    {
        $this->revisionsCount = $revisionsCount;
    }
}
