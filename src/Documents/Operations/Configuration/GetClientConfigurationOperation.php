<?php

namespace RavenDB\Documents\Operations\Configuration;

use RavenDB\Http\RavenCommand;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;

// !status: DONE
class GetClientConfigurationOperation implements MaintenanceOperationInterface
{
    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetClientConfigurationCommand();
    }
}
