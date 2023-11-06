<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;

class GetOperationStateOperation implements MaintenanceOperationInterface
{
    private int $id;
    private ?string $nodeTag;

    public function __construct(int $id, ?string $nodeTag = null)
    {
        $this->id = $id;
        $this->nodeTag = $nodeTag;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetOperationStateCommand($this->id, $this->nodeTag);
    }
}
