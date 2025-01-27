<?php

namespace RavenDB\ServerWide\Operations\Configuration;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\Configuration\ClientConfiguration;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\ServerWide\Operations\VoidServerOperationInterface;

class PutServerWideClientConfigurationOperation implements VoidServerOperationInterface
{
    private ?ClientConfiguration $configuration = null;

    public function __construct(?ClientConfiguration $configuration)
    {
        if ($configuration == null) {
            throw new IllegalArgumentException("Configuration cannot be null");
        }

        $this->configuration = $configuration;
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new PutServerWideClientConfigurationCommand($conventions, $this->configuration);
    }
}
