<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use DateTime;
use Symfony\Component\Serializer\Annotation\SerializedName;

class OngoingTaskSubscription extends OngoingTask
{
    #[SerializedName("Query")]
    private ?string $query = null;

    #[SerializedName("SubscriptionName")]
    private ?string $subscriptionName = null;

    #[SerializedName("SubscriptionId")]
    private ?int $subscriptionId = null;

    #[SerializedName("ChangeVectorForNextBatchStartingPoint")]
    private ?string $changeVectorForNextBatchStartingPoint = null;

    #[SerializedName("LastBatchAckTime")]
    private ?DateTime $lastBatchAckTime = null;

    #[SerializedName("Disabled")]
    private bool $disabled = false;

    #[SerializedName("LastClientConnectionTime")]
    private ?DateTime $lastClientConnectionTime = null;

    public function __construct()
    {
        $this->setTaskType(OngoingTaskType::subscription());
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): void
    {
        $this->query = $query;
    }

    public function getSubscriptionName(): ?string
    {
        return $this->subscriptionName;
    }

    public function setSubscriptionName(?string $subscriptionName): void
    {
        $this->subscriptionName = $subscriptionName;
    }

    public function getSubscriptionId(): ?int
    {
        return $this->subscriptionId;
    }

    public function setSubscriptionId(?int $subscriptionId): void
    {
        $this->subscriptionId = $subscriptionId;
    }

    public function getChangeVectorForNextBatchStartingPoint(): ?string
    {
        return $this->changeVectorForNextBatchStartingPoint;
    }

    public function setChangeVectorForNextBatchStartingPoint(?string $changeVectorForNextBatchStartingPoint): void
    {
        $this->changeVectorForNextBatchStartingPoint = $changeVectorForNextBatchStartingPoint;
    }

    public function getLastBatchAckTime(): ?DateTime
    {
        return $this->lastBatchAckTime;
    }

    public function setLastBatchAckTime(?DateTime $lastBatchAckTime): void
    {
        $this->lastBatchAckTime = $lastBatchAckTime;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function getLastClientConnectionTime(): ?DateTime
    {
        return $this->lastClientConnectionTime;
    }

    public function setLastClientConnectionTime(?DateTime $lastClientConnectionTime): void
    {
        $this->lastClientConnectionTime = $lastClientConnectionTime;
    }
}
