<?php

namespace RavenDB\Documents\Operations\Revisions;

// !status: DONE
class RevisionsCollectionConfiguration
{
    private int $minimumRevisionsToKeep = 0;

    private \DateInterval $minimumRevisionAgeToKeep;

    private bool $disabled = false;

    private bool $purgeOnDelete = false;

    public function getMinimumRevisionsToKeep(): int
    {
        return $this->minimumRevisionsToKeep;
    }

    public function setMinimumRevisionsToKeep(int $minimumRevisionsToKeep): void
    {
        $this->minimumRevisionsToKeep = $minimumRevisionsToKeep;
    }

    public function getMinimumRevisionAgeToKeep(): \DateInterval
    {
        return $this->minimumRevisionAgeToKeep;
    }

    public function setMinimumRevisionAgeToKeep(\DateInterval $minimumRevisionAgeToKeep): void
    {
        $this->minimumRevisionAgeToKeep = $minimumRevisionAgeToKeep;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function isPurgeOnDelete(): bool
    {
        return $this->purgeOnDelete;
    }

    public function setPurgeOnDelete(bool $purgeOnDelete): void
    {
        $this->purgeOnDelete = $purgeOnDelete;
    }
}
