<?php

namespace RavenDB\Documents\Operations\Etl\Queue;

use RavenDB\Documents\Operations\ConnectionStrings\ConnectionString;
use RavenDB\ServerWide\ConnectionStringType;

class QueueConnectionString extends ConnectionString
{
    private ?QueueBrokerType $brokerType = null;

    private ?KafkaConnectionSettings $kafkaConnectionSettings = null;
    private ?RabbitMqConnectionSettings $rabbitMqConnectionSettings = null;

    public function getType(): ConnectionStringType
    {
        return ConnectionStringType::queue();
    }

    public function getBrokerType(): ?QueueBrokerType
    {
        return $this->brokerType;
    }

    public function setBrokerType(?QueueBrokerType $brokerType): void
    {
        $this->brokerType = $brokerType;
    }

    public function getKafkaConnectionSettings(): ?KafkaConnectionSettings
    {
        return $this->kafkaConnectionSettings;
    }

    public function setKafkaConnectionSettings(?KafkaConnectionSettings $kafkaConnectionSettings): void
    {
        $this->kafkaConnectionSettings = $kafkaConnectionSettings;
    }

    public function getRabbitMqConnectionSettings(): ?RabbitMqConnectionSettings
    {
        return $this->rabbitMqConnectionSettings;
    }

    public function setRabbitMqConnectionSettings(?RabbitMqConnectionSettings $rabbitMqConnectionSettings): void
    {
        $this->rabbitMqConnectionSettings = $rabbitMqConnectionSettings;
    }
}
