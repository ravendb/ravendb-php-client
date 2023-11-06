<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;

class GetStatisticsOperation implements MaintenanceOperationInterface
{
    private ?string $debugTag = null;
    private ?string $nodeTag = null;

    public function __construct(?string $debugTag = null, ?string $nodeTag = null)
    {
        $this->debugTag = $debugTag;
        $this->nodeTag = $nodeTag;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetStatisticsCommand($this->debugTag, $this->nodeTag);
    }
}
