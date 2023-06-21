<?php

namespace RavenDB\Documents\Operations\Expiration;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Http\RavenCommand;

class ConfigureExpirationOperation implements MaintenanceOperationInterface
{
    private ?ExpirationConfiguration $configuration = null;

    public function __construct(?ExpirationConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new ConfigureExpirationCommand($this->configuration);
    }
}
