<?php

namespace RavenDB\Documents\Operations;

class PatchPayload
{
    private ?PatchRequest $patch = null;
    private ?PatchRequest $patchIfMissing = null;

    public function __construct(?PatchRequest $patch, ?PatchRequest $patchIfMissing)
    {
        $this->patch          = $patch;
        $this->patchIfMissing = $patchIfMissing;
    }

    public function getPatch(): ?PatchRequest
    {
        return $this->patch;
    }

    public function getPatchIfMissing(): ?PatchRequest
    {
        return $this->patchIfMissing;
    }
}
