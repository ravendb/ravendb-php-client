<?php

namespace RavenDB\Documents\Operations\Etl;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Http\RavenCommand;

class AddEtlOperation implements MaintenanceOperationInterface
{
    private ?EtlConfiguration $configuration = null;

    public function __construct(?EtlConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new AddEtlCommand($this->configuration);
    }
}
