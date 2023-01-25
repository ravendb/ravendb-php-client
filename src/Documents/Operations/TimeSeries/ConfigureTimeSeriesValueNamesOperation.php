<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class ConfigureTimeSeriesValueNamesOperation implements MaintenanceOperationInterface
{
    private ?ConfigureTimeSeriesValueNamesParameters $parameters = null;

    public function __construct(?ConfigureTimeSeriesValueNamesParameters $parameters)
    {
        if ($parameters == null) {
            throw new IllegalArgumentException("Parameters cannot be null");
        }
        $this->parameters = $parameters;
        $this->parameters->validate();
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new ConfigureTimeSeriesValueNamesCommand($this->parameters);
    }
}
