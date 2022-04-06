<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;

interface MaintenanceOperationInterface
{
    public function getCommand(DocumentConventions $conventions): RavenCommand;
}
