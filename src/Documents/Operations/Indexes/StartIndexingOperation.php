<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Http\VoidRavenCommand;

class StartIndexingOperation implements VoidMaintenanceOperationInterface
{
    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new StartIndexingCommand();
    }
}
