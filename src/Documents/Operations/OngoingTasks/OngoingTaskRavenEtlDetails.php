<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Documents\Operations\Etl\RavenEtlConfiguration;
use Symfony\Component\Serializer\Annotation\SerializedName;

class OngoingTaskRavenEtlDetails extends OngoingTask
{
    #[SerializedName("DestinationUrl")]
    private ?string $destinationUrl = null;

    #[SerializedName("Configuration")]
    private ?RavenEtlConfiguration $configuration = null;

    public function __construct()
    {
        $this->setTaskType(OngoingTaskType::ravenEtl());
    }

    public function getDestinationUrl(): ?string
    {
        return $this->destinationUrl;
    }

    public function setDestinationUrl(?string $destinationUrl): void
    {
        $this->destinationUrl = $destinationUrl;
    }

    public function getConfiguration(): ?RavenEtlConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?RavenEtlConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }
}
