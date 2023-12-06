<?php

namespace RavenDB\ServerWide;

use DateTime;
use RavenDB\ServerWide\Operations\DatabasePromotionStatusMap;
use RavenDB\Type\StringList;
use RavenDB\Type\StringMap;

use Symfony\Component\Serializer\Annotation\SerializedName;

class DatabaseTopology
{
    #[SerializedName("Members")]
    private ?StringList $members = null;
    #[SerializedName("Promotables")]
    private ?StringList $promotables = null;
    #[SerializedName("Rehabs")]
    private ?StringList $rehabs = null;

    #[SerializedName("PredefinedMentors")]
    private ?StringMap $predefinedMentors = null;
    #[SerializedName("DemotionReasons")]
    private ?StringMap $demotionReasons = null;
    #[SerializedName("PromotablesStatus")]
    private ?DatabasePromotionStatusMap $promotablesStatus = null;
    #[SerializedName("ReplicationFactor")]
    private int $replicationFactor = 1;
    #[SerializedName("DynamicNodesDistribution")]
    private bool $dynamicNodesDistribution = false;
    #[SerializedName("Stamp")]
    private ?LeaderStamp $stamp = null;
    #[SerializedName("DatabaseTopologyIdBase64")]
    private ?string $databaseTopologyIdBase64 = null;
    #[SerializedName("ClusterTransactionIdBase64")]
    private ?string $clusterTransactionIdBase64 = null;
    #[SerializedName("PriorityOrder")]
    private ?StringList $priorityOrder = null;
    #[SerializedName("NodesModifiedAt")]
    private ?DateTime $nodesModifiedAt = null;

    public function getMembers(): ?StringList
    {
        return $this->members;
    }

    public function setMembers(?StringList $members): void
    {
        $this->members = $members;
    }

    public function getPromotables(): ?StringList
    {
        return $this->promotables;
    }

    public function setPromotables(?StringList $promotables): void
    {
        $this->promotables = $promotables;
    }

    public function getRehabs(): ?StringList
    {
        return $this->rehabs;
    }

    public function setRehabs(?StringList $rehabs): void
    {
        $this->rehabs = $rehabs;
    }

    public function getPredefinedMentors(): ?StringMap
    {
        return $this->predefinedMentors;
    }

    public function setPredefinedMentors(?StringMap $predefinedMentors): void
    {
        $this->predefinedMentors = $predefinedMentors;
    }

    public function getDemotionReasons(): ?StringMap
    {
        return $this->demotionReasons;
    }

    public function setDemotionReasons(?StringMap $demotionReasons): void
    {
        $this->demotionReasons = $demotionReasons;
    }

    public function getPromotablesStatus(): ?DatabasePromotionStatusMap
    {
        return $this->promotablesStatus;
    }

    public function setPromotablesStatus(?DatabasePromotionStatusMap $promotablesStatus): void
    {
        $this->promotablesStatus = $promotablesStatus;
    }

    public function getReplicationFactor(): int
    {
        return $this->replicationFactor;
    }

    public function setReplicationFactor(int $replicationFactor): void
    {
        $this->replicationFactor = $replicationFactor;
    }

    public function isDynamicNodesDistribution(): bool
    {
        return $this->dynamicNodesDistribution;
    }

    public function setDynamicNodesDistribution(bool $dynamicNodesDistribution): void
    {
        $this->dynamicNodesDistribution = $dynamicNodesDistribution;
    }

    public function getStamp(): ?LeaderStamp
    {
        return $this->stamp;
    }

    public function setStamp(?LeaderStamp $stamp): void
    {
        $this->stamp = $stamp;
    }

    public function getDatabaseTopologyIdBase64(): ?string
    {
        return $this->databaseTopologyIdBase64;
    }

    public function setDatabaseTopologyIdBase64(?string $databaseTopologyIdBase64): void
    {
        $this->databaseTopologyIdBase64 = $databaseTopologyIdBase64;
    }

    public function getClusterTransactionIdBase64(): ?string
    {
        return $this->clusterTransactionIdBase64;
    }

    public function setClusterTransactionIdBase64(?string $clusterTransactionIdBase64): void
    {
        $this->clusterTransactionIdBase64 = $clusterTransactionIdBase64;
    }

    public function getPriorityOrder(): ?StringList
    {
        return $this->priorityOrder;
    }

    public function setPriorityOrder(?StringList $priorityOrder): void
    {
        $this->priorityOrder = $priorityOrder;
    }

    public function getNodesModifiedAt(): ?DateTime
    {
        return $this->nodesModifiedAt;
    }

    public function setNodesModifiedAt(?DateTime $nodesModifiedAt): void
    {
        $this->nodesModifiedAt = $nodesModifiedAt;
    }
}
