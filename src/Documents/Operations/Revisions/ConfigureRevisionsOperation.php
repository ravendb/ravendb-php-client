<?php

namespace RavenDB\Documents\Operations\Revisions;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class ConfigureRevisionsOperation implements MaintenanceOperationInterface
{
    private ?RevisionsConfiguration $configuration = null;

    public function __construct(?RevisionsConfiguration $configuration)
    {
        if ($configuration == null) {
            throw new IllegalArgumentException("Configuration cannot be null");
        }
        $this->configuration = $configuration;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new ConfigureRevisionsCommand($this->configuration);
    }
}
