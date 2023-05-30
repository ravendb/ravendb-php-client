<?php

namespace RavenDB\Documents\Operations\ConnectionStrings;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Http\RavenCommand;

class PutConnectionStringOperation implements MaintenanceOperationInterface
{
    private mixed $connectionString = null;

    public function __construct(mixed $connectionString)
    {
        $this->connectionString = $connectionString;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new PutConnectionStringCommand($this->connectionString);
    }
}
