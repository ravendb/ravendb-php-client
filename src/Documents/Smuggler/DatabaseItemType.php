<?php

namespace RavenDB\Documents\Smuggler;

use RavenDB\Type\ValueObjectInterface;

class DatabaseItemType implements ValueObjectInterface
{
    private const NONE = 'None';
    private const DOCUMENTS = 'Documents';
    private const REVISION_DOCUMENTS = 'RevisionDocuments';
    private const INDEXES = 'Indexes';
    private const IDENTITIES = 'Identities';
    private const TOMBSTONES = 'Tombstones';
    private const LEGACY_ATTACHMENTS = 'LegacyAttachments';
    private const CONFLICTS = 'Conflicts';
    private const COMPARE_EXCHANGE = 'CompareExchange';
    private const LEGACY_DOCUMENT_DELETIONS = 'LegacyDocumentDeletions';
    private const LEGACY_ATTACHMENT_DELETIONS = 'LegacyAttachmentDeletions';
    private const DATABASE_RECORD = 'DatabaseRecord';
    private const UNKNOWN = 'Unknown';
    private const COUNTERS = 'Counters';
    private const ATTACHMENTS = 'Attachments';
    private const COUNTER_GROUPS = 'CounterGroups';
    private const SUBSCRIPTIONS = 'Subscriptions';
    private const COMPARE_EXCHANGE_TOMBSTONES = 'CompareExchangeTombstones';
    private const TIME_SERIES = 'TimeSeries';
    private const REPLICATION_HUB_CERTIFICATES = 'ReplicationHubCertificates';

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

    public static function none(): DatabaseItemType
    {
        return new DatabaseItemType(self::NONE);
    }

    public function isDocuments(): bool
    {
        return $this->value == self::DOCUMENTS;
    }

    public static function documents(): DatabaseItemType
    {
        return new DatabaseItemType(self::DOCUMENTS);
    }

    public function isRevisionDocuments(): bool
    {
        return $this->value == self::REVISION_DOCUMENTS;
    }

    public static function revisionDocuments(): DatabaseItemType
    {
        return new DatabaseItemType(self::REVISION_DOCUMENTS);
    }

    public function isIndexes(): bool
    {
        return $this->value == self::INDEXES;
    }

    public static function indexes(): DatabaseItemType
    {
        return new DatabaseItemType(self::INDEXES);
    }

    public function isIdentities(): bool
    {
        return $this->value == self::IDENTITIES;
    }

    public static function identities(): DatabaseItemType
    {
        return new DatabaseItemType(self::IDENTITIES);
    }

    public function isTombstones(): bool
    {
        return $this->value == self::TOMBSTONES;
    }

    public static function tombstones(): DatabaseItemType
    {
        return new DatabaseItemType(self::TOMBSTONES);
    }

    public function isLegacyAttachments(): bool
    {
        return $this->value == self::LEGACY_ATTACHMENTS;
    }

    public static function legacyAttachments(): DatabaseItemType
    {
        return new DatabaseItemType(self::LEGACY_ATTACHMENTS);
    }

    public function isConflicts(): bool
    {
        return $this->value == self::CONFLICTS;
    }

    public static function conflicts(): DatabaseItemType
    {
        return new DatabaseItemType(self::CONFLICTS);
    }

    public function isCompareExchange(): bool
    {
        return $this->value == self::COMPARE_EXCHANGE;
    }

    public static function compareExchange(): DatabaseItemType
    {
        return new DatabaseItemType(self::COMPARE_EXCHANGE);
    }

    public function isLegacyDocumentDeletions(): bool
    {
        return $this->value == self::LEGACY_DOCUMENT_DELETIONS;
    }

    public static function legacyDocumentDeletions(): DatabaseItemType
    {
        return new DatabaseItemType(self::LEGACY_DOCUMENT_DELETIONS);
    }

    public function isLegacyAttachmentDeletions(): bool
    {
        return $this->value == self::LEGACY_ATTACHMENT_DELETIONS;
    }

    public static function legacyAttachmentDeletions(): DatabaseItemType
    {
        return new DatabaseItemType(self::LEGACY_ATTACHMENT_DELETIONS);
    }

    public function isDatabaseRecord(): bool
    {
        return $this->value == self::DATABASE_RECORD;
    }

    public static function databaseRecord(): DatabaseItemType
    {
        return new DatabaseItemType(self::DATABASE_RECORD);
    }

    public function isUnknown(): bool
    {
        return $this->value == self::UNKNOWN;
    }

    public static function unknown(): DatabaseItemType
    {
        return new DatabaseItemType(self::UNKNOWN);
    }

    public function isCounters(): bool
    {
        return $this->value == self::COUNTERS;
    }

    public static function counters(): DatabaseItemType
    {
        return new DatabaseItemType(self::COUNTERS);
    }

    public function isAttachments(): bool
    {
        return $this->value == self::ATTACHMENTS;
    }

    public static function attachments(): DatabaseItemType
    {
        return new DatabaseItemType(self::ATTACHMENTS);
    }

    public function isCounterGroups(): bool
    {
        return $this->value == self::COUNTER_GROUPS;
    }

    public static function counterGroups(): DatabaseItemType
    {
        return new DatabaseItemType(self::COUNTER_GROUPS);
    }

    public function isSubscriptions(): bool
    {
        return $this->value == self::SUBSCRIPTIONS;
    }

    public static function subscriptions(): DatabaseItemType
    {
        return new DatabaseItemType(self::SUBSCRIPTIONS);
    }

    public function isCompareExchangeTombstones(): bool
    {
        return $this->value == self::COMPARE_EXCHANGE_TOMBSTONES;
    }

    public static function compareExchangeTombstones(): DatabaseItemType
    {
        return new DatabaseItemType(self::COMPARE_EXCHANGE_TOMBSTONES);
    }

    public function isTimeSeries(): bool
    {
        return $this->value == self::TIME_SERIES;
    }

    public static function timeSeries(): DatabaseItemType
    {
        return new DatabaseItemType(self::TIME_SERIES);
    }

    public function isReplicationHubCertificates(): bool
    {
        return $this->value == self::REPLICATION_HUB_CERTIFICATES;
    }

    public static function replicationHubCertificates(): DatabaseItemType
    {
        return new DatabaseItemType(self::REPLICATION_HUB_CERTIFICATES);
    }
}
