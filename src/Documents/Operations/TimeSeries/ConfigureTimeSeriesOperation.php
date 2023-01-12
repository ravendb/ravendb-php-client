<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class ConfigureTimeSeriesOperation implements MaintenanceOperationInterface
{
    private ?TimeSeriesConfiguration $configuration = null;

    public function __construct(?TimeSeriesConfiguration $configuration)
    {
        if ($configuration == null) {
            throw new IllegalArgumentException("Configuration cannot be null");
        }

        $this->configuration = $configuration;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new ConfigureTimeSeriesCommand($this->configuration);
    }
}
