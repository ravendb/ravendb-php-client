<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class RemoveTimeSeriesPolicyOperation implements MaintenanceOperationInterface
{
    private ?string $collection = null;
    private ?string $name = null;

    public function __construct(?string $collection, ?string $name)
    {
        if ($collection == null) {
            throw new IllegalArgumentException("Collection cannot be null");
        }
        if ($name == null) {
            throw new IllegalArgumentException("Name cannot be null");
        }

        $this->collection = $collection;
        $this->name = $name;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new RemoveTimeSeriesPolicyCommand($this->collection, $this->name);
    }
}
