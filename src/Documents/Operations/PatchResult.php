<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\ResultInterface;

class PatchResult extends PatchResultBase implements ResultInterface
{
    private ?array $originalDocument = null;
    private ?array $debug = null;

    public function getOriginalDocument(): ?array
    {
        return $this->originalDocument;
    }

    public function setOriginalDocument(?array $originalDocument): void
    {
        $this->originalDocument = $originalDocument;
    }

    public function getDebug(): ?array
    {
        return $this->debug;
    }

    public function setDebug(?array $debug): void
    {
        $this->debug = $debug;
    }
}
