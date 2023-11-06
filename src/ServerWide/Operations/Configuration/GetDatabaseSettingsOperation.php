<?php

namespace RavenDB\ServerWide\Operations\Configuration;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class GetDatabaseSettingsOperation implements MaintenanceOperationInterface
{
    private ?string $databaseName = null;

    public function __construct(?string $databaseName)
    {
        if (empty($databaseName)) {
            throw new IllegalArgumentException('DatabaseName cannot be null');
        }
        $this->databaseName = $databaseName;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetDatabaseSettingsCommand($this->databaseName);
    }
}
