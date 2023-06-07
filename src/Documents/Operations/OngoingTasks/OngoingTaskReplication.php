
<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Type\Duration;
use RavenDB\Type\StringArray;
use Symfony\Component\Serializer\Annotation\SerializedName;

class OngoingTaskReplication extends OngoingTask
{
    #[SerializedName("DestinationUrl")]
    private ?string $destinationUrl = null;

    #[SerializedName("TopologyDiscoveryUrls")]
    private ?StringArray $topologyDiscoveryUrls = null;

    #[SerializedName("DestinationDatabase")]
    private ?string $destinationDatabase = null;

    #[SerializedName("ConnectionStringName")]
    private ?string $connectionStringName = null;

    #[SerializedName("DelayReplicationFor")]
    private ?Duration $delayReplicationFor = null;

    public function __construct()
    {
        $this->setTaskType(OngoingTaskType::replication());
    }

    public function getDestinationUrl(): ?string
    {
        return $this->destinationUrl;
    }

    public function setDestinationUrl(?string $destinationUrl): void
    {
        $this->destinationUrl = $destinationUrl;
    }

    public function getTopologyDiscoveryUrls(): ?StringArray
    {
        return $this->topologyDiscoveryUrls;
    }

    public function setTopologyDiscoveryUrls(?StringArray $topologyDiscoveryUrls): void
    {
        $this->topologyDiscoveryUrls = $topologyDiscoveryUrls;
    }

    public function getDestinationDatabase(): ?string
    {
        return $this->destinationDatabase;
    }

    public function setDestinationDatabase(?string $destinationDatabase): void
    {
        $this->destinationDatabase = $destinationDatabase;
    }

    public function getConnectionStringName(): ?string
    {
        return $this->connectionStringName;
    }

    public function setConnectionStringName(?string $connectionStringName): void
    {
        $this->connectionStringName = $connectionStringName;
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
