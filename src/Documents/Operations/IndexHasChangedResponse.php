<?php

namespace RavenDB\Documents\Operations;

use Symfony\Component\Serializer\Annotation\SerializedName;

class IndexHasChangedResponse
{
    /** @SerializedName ("Changed") */
    private bool $changed = false;

    public function isChanged(): bool
    {
        return $this->changed;
    }

    public function setChanged(bool $changed): void
    {
        $this->changed = $changed;
    }
}
