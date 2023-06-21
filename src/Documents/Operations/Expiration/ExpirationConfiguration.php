<?php

namespace RavenDB\Documents\Operations\Expiration;

class ExpirationConfiguration
{
    private bool $disabled = false;
    private ?int $deleteFrequencyInSec = null;

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function getDeleteFrequencyInSec(): ?int
    {
        return $this->deleteFrequencyInSec;
    }

    public function setDeleteFrequencyInSec(?int $deleteFrequencyInSec): void
    {
        $this->deleteFrequencyInSec = $deleteFrequencyInSec;
    }
}
