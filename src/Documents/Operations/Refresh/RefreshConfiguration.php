<?php

namespace RavenDB\Documents\Operations\Refresh;

use Symfony\Component\Serializer\Annotation\SerializedName;

class RefreshConfiguration
{
    /** @SerializedName ("Disabled") */
    private bool $disabled = false;

    /** @SerializedName ("RefreshFrequencyInSec") */
    private ?int $refreshFrequencyInSec = null;

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function getRefreshFrequencyInSec(): ?int
    {
        return $this->refreshFrequencyInSec;
    }

    public function setRefreshFrequencyInSec(?int $refreshFrequencyInSec): void
    {
        $this->refreshFrequencyInSec = $refreshFrequencyInSec;
    }
}
