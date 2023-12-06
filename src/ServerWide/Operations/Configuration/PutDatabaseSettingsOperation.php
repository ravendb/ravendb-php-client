<?php

namespace RavenDB\ServerWide\Operations\Configuration;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Type\StringMap;

class PutDatabaseSettingsOperation implements VoidMaintenanceOperationInterface
{
    private ?string $databaseName = null;
    private ?StringMap $configurationSettings = null;

    public function __construct(?string $databaseName, StringMap|array|null $configurationSettings)
    {
        if (empty($databaseName)) {
            throw new IllegalArgumentException('DatabaseName cannot be null');
        }
        $this->databaseName = $databaseName;

        if ($configurationSettings == null) {
            throw new IllegalArgumentException("ConfigurationSettings cannot be null");
        }
        $this->configurationSettings = $configurationSettings;
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new PutDatabaseConfigurationSettingsCommand($this->configurationSettings, $this->databaseName);
    }
}
