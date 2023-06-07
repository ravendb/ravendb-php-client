<?php

namespace RavenDB\Documents\Operations\Backups;

use RavenDB\Type\Duration;

class RetentionPolicy
{
    private bool $disabled = false;
    private ?Duration $minimumBackupAgeToKeep = null;

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function getMinimumBackupAgeToKeep(): ?Duration
    {
        return $this->minimumBackupAgeToKeep;
    }

    public function setMinimumBackupAgeToKeep(?Duration $minimumBackupAgeToKeep): void
    {
        $this->minimumBackupAgeToKeep = $minimumBackupAgeToKeep;
    }
}
