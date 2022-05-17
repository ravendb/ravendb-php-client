<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\VoidRavenCommand;

/**
 * Represents admin operation which doesn't return any response
 */
interface VoidMaintenanceOperationInterface extends MaintenanceOperationInterface
{
    function getCommand(DocumentConventions $conventions): VoidRavenCommand;
}
