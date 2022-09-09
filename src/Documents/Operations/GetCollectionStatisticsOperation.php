<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;

// !status: DONE
class GetCollectionStatisticsOperation implements MaintenanceOperationInterface
{
    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetCollectionStatisticsCommand();
    }
}
