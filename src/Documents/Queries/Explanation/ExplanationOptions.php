<?php

namespace RavenDB\Documents\Queries\Explanation;

class ExplanationOptions
{
    private ?string $groupKey;

    public function getGroupKey(): ?string
    {
        return $this->groupKey;
    }

    public function setGroupKey(?string $groupKey): void
    {
        $this->groupKey = $groupKey;
    }
}
