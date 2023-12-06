<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;

class GetServerWideOperationStateOperation implements ServerOperationInterface
{
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetServerWideOperationStateCommand($this->id);
    }
}
