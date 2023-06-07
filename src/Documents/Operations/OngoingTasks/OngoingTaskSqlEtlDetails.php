<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Documents\Operations\Etl\Sql\SqlEtlConfiguration;
use Symfony\Component\Serializer\Annotation\SerializedName;

class OngoingTaskSqlEtlDetails extends OngoingTask
{
    #[SerializedName("Configuration")]
    private ?SqlEtlConfiguration $configuration = null;

    public function __construct()
    {
        $this->setTaskType(OngoingTaskType::sqlEtl());
    }

    public function getConfiguration(): ?SqlEtlConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?SqlEtlConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }
}
