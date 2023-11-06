<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Exceptions\IllegalArgumentException;

class CommandType
{
    const NONE = 'None';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';
    const ATTACHMENT_PUT = 'AttachmentPUT';
    const ATTACHMENT_DELETE = 'AttachmentDELETE';
    const ATTACHMENT_MOVE = 'AttachmentMOVE';
    const ATTACHMENT_COPY = 'AttachmentCOPY';
    const COMPARE_EXCHANGE_PUT = 'CompareExchangePUT';
    const COMPARE_EXCHANGE_DELETE = 'CompareExchangeDELETE';

    const FORCE_REVISION_CREATION = 'ForceRevisionCreation';

    const COUNTERS = 'Counters';
    const TIME_SERIES = 'TimeSeries';
    const TIME_SERIES_BULK_INSERT = 'TimeSeriesBulkInsert';
    const TIME_SERIES_COPY = 'TimeSeriesCopy';

    const BATCH_PATCH = 'BatchPATCH';

    const CLIENT_ANY_COMMAND = 'ClientAndCommand';
    const CLIENT_MODIFY_DOCUMENT_COMMAND = 'ClientModifyDocumentCommand';

    private static array $allowedValues = [
        self::NONE,
        self::PUT,
        self::PATCH,
        self::DELETE,
        self::ATTACHMENT_PUT,
        self::ATTACHMENT_DELETE,
        self::ATTACHMENT_MOVE,
        self::ATTACHMENT_COPY,
        self::COMPARE_EXCHANGE_PUT,
        self::COMPARE_EXCHANGE_DELETE,
        self::FORCE_REVISION_CREATION,
        self::COUNTERS,
        self::TIME_SERIES,
        self::TIME_SERIES_BULK_INSERT,
        self::TIME_SERIES_COPY,
        self::BATCH_PATCH,
        self::CLIENT_ANY_COMMAND,
        self::CLIENT_MODIFY_DOCUMENT_COMMAND,
    ];

    private string $value = '';

    private function __construct(string $value)
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
        if (!in_array($value, self::$allowedValues)) {
            throw new IllegalArgumentException('Unable to parse type: ' . $value);
        }
        $this->value = $value;
    }

    public function equals(CommandType $that): bool
    {
        return $this->value == $that->getValue();
    }

    // Checkers

    public function isNone(): bool
    {
        return $this->value == self::NONE;
    }

    public function isPut(): bool
    {
        return $this->value == self::PUT;
    }

    public function isPatch(): bool
    {
        return $this->value == self::PATCH;
    }

    public function isDelete(): bool
    {
        return $this->value == self::DELETE;
    }

    public function isAttachmentPut(): bool
    {
        return $this->value == self::ATTACHMENT_PUT;
    }

    public function isAttachmentDelete(): bool
    {
        return $this->value == self::ATTACHMENT_DELETE;
    }

    public function isAttachmentCopy(): bool
    {
        return $this->value == self::ATTACHMENT_COPY;
    }

    public function isAttachmentMove(): bool
    {
        return $this->value == self::ATTACHMENT_MOVE;
    }

    public function isCompareExchangePut(): bool
    {
        return $this->value == self::COMPARE_EXCHANGE_PUT;
    }

    public function isCompareExchangeDelete(): bool
    {
        return $this->value == self::COMPARE_EXCHANGE_DELETE;
    }

    public function isForceRevisionCreation(): bool
    {
        return $this->value == self::FORCE_REVISION_CREATION;
    }

    public function isCounters(): bool
    {
        return $this->value == self::COUNTERS;
    }

    public function isTimeSeries(): bool
    {
        return $this->value == self::TIME_SERIES;
    }

    public function isTimeSeriesBulkInsert(): bool
    {
        return $this->value == self::TIME_SERIES_BULK_INSERT;
    }

    public function isTimeSeriesCopy(): bool
    {
        return $this->value == self::TIME_SERIES_COPY;
    }

    public function isBatchPatch(): bool
    {
        return $this->value == self::BATCH_PATCH;
    }

    public function isClientAndCommand(): bool
    {
        return $this->value == self::CLIENT_ANY_COMMAND;
    }

    public function isClientModifyDocumentCommand(): bool
    {
        return $this->value == self::CLIENT_MODIFY_DOCUMENT_COMMAND;
    }

    // Named constructors
    public static function parseCSharpValue(string $type): CommandType
    {
        return new CommandType($type);
    }

    public static function none(): CommandType
    {
        return new CommandType(self::NONE);
    }
    public static function put(): CommandType
    {
        return new CommandType(self::PUT);
    }
    public static function patch(): CommandType
    {
        return new CommandType(self::PATCH);
    }
    public static function delete(): CommandType
    {
        return new CommandType(self::DELETE);
    }
    public static function attachmentPut(): CommandType
    {
        return new CommandType(self::ATTACHMENT_PUT);
    }
    public static function attachmentDelete(): CommandType
    {
        return new CommandType(self::ATTACHMENT_DELETE);
    }
    public static function attachmentMove(): CommandType
    {
        return new CommandType(self::ATTACHMENT_MOVE);
    }
    public static function attachmentCopy(): CommandType
    {
        return new CommandType(self::ATTACHMENT_COPY);
    }
    public static function compareExchangePut(): CommandType
    {
        return new CommandType(self::COMPARE_EXCHANGE_PUT);
    }
    public static function compareExchangeDelete(): CommandType
    {
        return new CommandType(self::COMPARE_EXCHANGE_DELETE);
    }
    public static function forceRevisionCreation(): CommandType
    {
        return new CommandType(self::FORCE_REVISION_CREATION);
    }
    public static function counters(): CommandType
    {
        return new CommandType(self::COUNTERS);
    }
    public static function timeSeries(): CommandType
    {
        return new CommandType(self::TIME_SERIES);
    }
    public static function timeSeriesBulkInsert(): CommandType
    {
        return new CommandType(self::TIME_SERIES_BULK_INSERT);
    }
    public static function timeSeriesCopy(): CommandType
    {
        return new CommandType(self::TIME_SERIES_COPY);
    }
    public static function batchPatch(): CommandType
    {
        return new CommandType(self::BATCH_PATCH);
    }
    public static function clientAnyCommand(): CommandType
    {
        return new CommandType(self::CLIENT_ANY_COMMAND);
    }
    public static function clientModifyDocumentCommand(): CommandType
    {
        return new CommandType(self::CLIENT_MODIFY_DOCUMENT_COMMAND);
    }
}
