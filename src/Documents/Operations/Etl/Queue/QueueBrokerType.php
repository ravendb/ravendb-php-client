<?php

namespace RavenDB\Documents\Operations\Etl\Queue;

use RavenDB\Type\ValueObjectInterface;

class QueueBrokerType implements ValueObjectInterface
{
    private const NONE = 'None';
    private const KAFKA = 'Kafka';
    private const RABBIT_MQ = 'RabbitMq';

    private string $value;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function isNone(): bool
    {
        return $this->value == self::NONE;
    }

    public static function none(): QueueBrokerType
    {
        return new QueueBrokerType(self::NONE);
    }

    public function isKafka(): bool
    {
        return $this->value == self::KAFKA;
    }

    public static function kafka(): QueueBrokerType
    {
        return new QueueBrokerType(self::KAFKA);
    }

    public function isRabbitMq(): bool
    {
        return $this->value == self::RABBIT_MQ;
    }

    public static function rabbitMq(): QueueBrokerType
    {
        return new QueueBrokerType(self::RABBIT_MQ);
    }
}
