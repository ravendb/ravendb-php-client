<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Documents\Operations\Etl\Queue\QueueEtlConfiguration;

class OngoingTaskQueueEtlDetails
{
    private ?QueueEtlConfiguration $configuration = null;

    public function getConfiguration(): ?QueueEtlConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?QueueEtlConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }
}
