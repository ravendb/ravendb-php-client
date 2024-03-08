<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Documents\Operations\Etl\ElasticSearch\ElasticSearchEtlConfiguration;

class OngoingTaskElasticSearchEtlDetails extends OngoingTask
{
    private ?ElasticSearchEtlConfiguration $configuration = null;

    public function __construct()
    {
        $this->setTaskType(OngoingTaskType::elasticSearchEtl());
    }

    public function getConfiguration(): ?ElasticSearchEtlConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?ElasticSearchEtlConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }
}
