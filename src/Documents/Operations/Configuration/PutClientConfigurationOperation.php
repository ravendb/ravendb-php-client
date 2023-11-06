<?php

namespace RavenDB\Documents\Operations\Configuration;

use RavenDB\Http\VoidRavenCommand;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;

class PutClientConfigurationOperation implements VoidMaintenanceOperationInterface
{
    private ?ClientConfiguration $configuration = null;

    public function __construct(?ClientConfiguration $configuration)
    {
        if ($configuration == null) {
            throw new IllegalArgumentException("Configuration cannot be null");
        }

        $this->configuration = $configuration;
    }

    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new PutClientConfigurationCommand($conventions, $this->configuration);
    }
}
