<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Documents\Operations\Etl\Olap\OlapEtlConfiguration;

class OngoingTaskOlapEtlDetails extends OngoingTask
{
    public function __construct() {
        $this->setTaskType(OngoingTaskType::olapEtl());
    }

    private ?OlapEtlConfiguration $configuration = null;

    public function getConfiguration(): ?OlapEtlConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?OlapEtlConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }
}
