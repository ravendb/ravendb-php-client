<?php

namespace RavenDB\Documents\Operations\Etl;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Http\RavenCommand;

class UpdateEtlOperation implements MaintenanceOperationInterface
{
    private ?int $taskId = null;
    private ?EtlConfiguration $configuration = null;

    public function __construct(?int $taskId, ?EtlConfiguration $configuration = null)
    {
        $this->taskId = $taskId;
        $this->configuration = $configuration;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new UpdateEtlCommand($this->taskId, $this->configuration);
    }
}
