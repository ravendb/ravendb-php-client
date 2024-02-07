<?php

namespace RavenDB\Documents\Operations\Etl\Queue;

use RavenDB\Documents\Operations\Etl\EtlType;

class QueueEtlConfiguration
{
    private ?EtlQueueList $queues = null;
    private ?QueueBrokerType $brokerType = null;
    private bool $skipAutomaticQueueDeclaration = false;

    public function getEtlType(): EtlType
    {
        return EtlType::queue();
    }

    public function getQueues(): ?EtlQueueList
    {
        return $this->queues;
    }

    public function setQueues(?EtlQueueList $queues): void
    {
        $this->queues = $queues;
    }

    public function getBrokerType(): ?QueueBrokerType
    {
        return $this->brokerType;
    }

    public function setBrokerType(?QueueBrokerType $brokerType): void
    {
        $this->brokerType = $brokerType;
    }

    public function isSkipAutomaticQueueDeclaration(): bool
    {
        return $this->skipAutomaticQueueDeclaration;
    }

    public function setSkipAutomaticQueueDeclaration(bool $skipAutomaticQueueDeclaration): void
    {
        $this->skipAutomaticQueueDeclaration = $skipAutomaticQueueDeclaration;
    }
}
