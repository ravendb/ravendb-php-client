<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\RavenCommand;
use RavenDB\Documents\Conventions\DocumentConventions;

class GetDetailedStatisticsOperation implements MaintenanceOperationInterface
{
    private ?string $debugTag = null;

    public function __construct(?string $debugTag = null)
    {
        $this->debugTag = $debugTag;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new DetailedDatabaseStatisticsCommand($this->debugTag);
    }
}
