<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Type\ValueObjectInterface;

class OngoingTaskType implements ValueObjectInterface
{
    public const REPLICATION = 'Replication';
    public const RAVEN_ETL = 'RavenEtl';
    public const SQL_ETL = 'SqlEtl';
    public const OLAP_ETL = 'OlapEtl';
    public const BACKUP = 'Backup';
    public const SUBSCRIPTION = 'Subscription';
    public const PULL_REPLICATION_AS_HUB = 'PullReplicationAsHub';
    public const PULL_REPLICATION_AS_SINK = 'PullReplicationAsSink';

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

    public function isReplication(): bool
    {
        return $this->value == self::REPLICATION;
    }

    public static function replication(): OngoingTaskType
    {
        return new OngoingTaskType(self::REPLICATION);
    }

    public function isRavenEtl(): bool
    {
        return $this->value == self::RAVEN_ETL;
    }

    public static function ravenEtl(): OngoingTaskType
    {
        return new OngoingTaskType(self::RAVEN_ETL);
    }

    public function isSqlEtl(): bool
    {
        return $this->value == self::SQL_ETL;
    }

    public static function sqlEtl(): OngoingTaskType
    {
        return new OngoingTaskType(self::SQL_ETL);
    }

    public function isOlapEtl(): bool
    {
        return $this->value == self::OLAP_ETL;
    }

    public static function olapEtl(): OngoingTaskType
    {
        return new OngoingTaskType(self::OLAP_ETL);
    }

    public function isBackup(): bool
    {
        return $this->value == self::BACKUP;
    }

    public static function backup(): OngoingTaskType
    {
        return new OngoingTaskType(self::BACKUP);
    }

    public function isSubscription(): bool
    {
        return $this->value == self::SUBSCRIPTION;
    }

    public static function subscription(): OngoingTaskType
    {
        return new OngoingTaskType(self::SUBSCRIPTION);
    }

    public function isPullReplicationAsHub(): bool
    {
        return $this->value == self::PULL_REPLICATION_AS_HUB;
    }

    public static function pullReplicationAsHub(): OngoingTaskType
    {
        return new OngoingTaskType(self::PULL_REPLICATION_AS_HUB);
    }

    public function isPullReplicationAsSink(): bool
    {
        return $this->value == self::PULL_REPLICATION_AS_SINK;
    }

    public static function pullReplicationAsSink(): OngoingTaskType
    {
        return new OngoingTaskType(self::PULL_REPLICATION_AS_SINK);
    }
}
