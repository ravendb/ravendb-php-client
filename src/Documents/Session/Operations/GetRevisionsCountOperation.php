<?php

namespace RavenDB\Documents\Session\Operations;

use RavenDB\Http\RavenCommand;

class GetRevisionsCountOperation
{
    private ?string $docId = null;

    public function __construct(?string $docId)
    {
        $this->docId = $docId;
    }

    public function createRequest(): RavenCommand
    {
        return new GetRevisionsCountCommand($this->docId);
    }
}
