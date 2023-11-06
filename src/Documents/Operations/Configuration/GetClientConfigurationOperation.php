<?php

namespace RavenDB\Documents\Operations\Configuration;

use RavenDB\Http\RavenCommand;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;

class GetClientConfigurationOperation implements MaintenanceOperationInterface
{
    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetClientConfigurationCommand();
    }
}
