<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Http\RavenCommand;

class ConfigureTimeSeriesPolicyOperation implements MaintenanceOperationInterface
{
    private ?string $collection = null;
    private ?TimeSeriesPolicy $config = null;

    public function __construct(?string $collection, ?TimeSeriesPolicy $config)
    {
        $this->collection = $collection;
        $this->config = $config;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new ConfigureTimeSeriesPolicyCommand($this->collection, $this->config);
    }
}
