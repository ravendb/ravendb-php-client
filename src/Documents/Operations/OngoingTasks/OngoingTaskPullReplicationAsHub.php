<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Type\Duration;
use Symfony\Component\Serializer\Annotation\SerializedName;

class OngoingTaskPullReplicationAsHub extends OngoingTask
{
    #[SerializedName("DestinationUrl")]
    private ?string $destinationUrl = null;

    #[SerializedName("DestinationDatabase")]
    private ?string $destinationDatabase = null;

    #[SerializedName("DelayReplicationFor")]
    private ?Duration $delayReplicationFor = null;

    public function __construct()
    {
        $this->setTaskType(OngoingTaskType::pullReplicationAsHub());
    }

    public function getDestinationUrl(): ?string
    {
        return $this->destinationUrl;
    }

    public function setDestinationUrl(?string $destinationUrl): void
    {
        $this->destinationUrl = $destinationUrl;
    }

    public function getDestinationDatabase(): ?string
    {
        return $this->destinationDatabase;
    }

    public function setDestinationDatabase(?string $destinationDatabase): void
    {
        $this->destinationDatabase = $destinationDatabase;
    }

    public function getDelayReplicationFor(): ?Duration
    {
        return $this->delayReplicationFor;
    }

    public function setDelayReplicationFor(?Duration $delayReplicationFor): void
    {
        $this->delayReplicationFor = $delayReplicationFor;
    }
}
