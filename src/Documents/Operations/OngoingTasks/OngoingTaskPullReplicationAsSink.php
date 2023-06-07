<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Documents\Operations\Replication\PullReplicationMode;
use RavenDB\Type\StringArray;
use Symfony\Component\Serializer\Annotation\SerializedName;

class OngoingTaskPullReplicationAsSink extends OngoingTask
{
    #[SerializedName("HubName")]
    private ?string $hubName = null;

    #[SerializedName("Mode")]
    private ?PullReplicationMode $mode = null;

    #[SerializedName("DestinationUrl")]
    private ?string $destinationUrl = null;

    #[SerializedName("TopologyDiscoveryUrls")]
    private ?StringArray $topologyDiscoveryUrls = null;

    #[SerializedName("DestinationDatabase")]
    private ?string $destinationDatabase = null;

    #[SerializedName("ConnectionStringName")]
    private ?string $connectionStringName = null;

    #[SerializedName("CertificatePublicKey")]
    private ?string $certificatePublicKey = null;

    #[SerializedName("AccessName")]
    private ?string $accessName = null;

    #[SerializedName("AllowedHubToSinkPaths")]
    private ?StringArray $allowedHubToSinkPaths = null;

    #[SerializedName("AllowedSinkToHubPaths")]
    private ?StringArray $allowedSinkToHubPaths = null;

    public function __construct()
    {
        $this->setTaskType(OngoingTaskType::pullReplicationAsSink());
    }

    public function getHubName(): ?string
    {
        return $this->hubName;
    }

    public function setHubName(?string $hubName): void
    {
        $this->hubName = $hubName;
    }

    public function getMode(): ?PullReplicationMode
    {
        return $this->mode;
    }

    public function setMode(?PullReplicationMode $mode): void
    {
        $this->mode = $mode;
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

    public function getCertificatePublicKey(): ?string
    {
        return $this->certificatePublicKey;
    }

    public function setCertificatePublicKey(?string $certificatePublicKey): void
    {
        $this->certificatePublicKey = $certificatePublicKey;
    }

    public function getAccessName(): ?string
    {
        return $this->accessName;
    }

    public function setAccessName(?string $accessName): void
    {
        $this->accessName = $accessName;
    }

    public function getAllowedHubToSinkPaths(): ?StringArray
    {
        return $this->allowedHubToSinkPaths;
    }

    public function setAllowedHubToSinkPaths(?StringArray $allowedHubToSinkPaths): void
    {
        $this->allowedHubToSinkPaths = $allowedHubToSinkPaths;
    }

    public function getAllowedSinkToHubPaths(): ?StringArray
    {
        return $this->allowedSinkToHubPaths;
    }

    public function setAllowedSinkToHubPaths(?StringArray $allowedSinkToHubPaths): void
    {
        $this->allowedSinkToHubPaths = $allowedSinkToHubPaths;
    }
}
