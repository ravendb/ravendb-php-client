<?php

namespace RavenDB\Documents\Operations\Refresh;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class ConfigureRefreshOperation implements MaintenanceOperationInterface
{
    private ?RefreshConfiguration $configuration = null;

    public function __construct(?RefreshConfiguration $configuration)
    {
        if ($configuration == null) {
            throw new IllegalArgumentException("Configuration cannot be null");
        }
        $this->configuration = $configuration;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new ConfigureRefreshCommand($this->configuration);
    }
}
