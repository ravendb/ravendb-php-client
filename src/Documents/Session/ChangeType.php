<?php

namespace RavenDB\Documents\Session;

use RavenDB\Exceptions\InvalidValueException;

class ChangeType
{
    public const NAME = 'ChangeType';

    public const DOCUMENT_DELETED = "DOCUMENT_DELETED";
    public const DOCUMENT_ADDED = "DOCUMENT_ADDED";
    public const FIELD_CHANGED = "FIELD_CHANGED";
    public const NEW_FIELD = "NEW_FIELD";
    public const REMOVED_FIELD = "REMOVED_FIELD";
    public const ARRAY_VALUE_CHANGED = "ARRAY_VALUE_CHANGED";
    public const ARRAY_VALUE_ADDED = "ARRAY_VALUE_ADDED";
    public const ARRAY_VALUE_REMOVED = "ARRAY_VALUE_REMOVED";
    public const UNKNOWN = "UNKNOWN";

    private string $value;

    /**
     * @throws InvalidValueException
     */
    public function __construct(?string $value)
    {
        $this->setValue($value);
    }

    /**
     * @throws InvalidValueException
     */
    public function setValue(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->value = self::UNKNOWN;
            return;
        }

        $upperCaseValue = strtoupper($value);
        $this->validateValue($upperCaseValue);
        $this->value = $upperCaseValue;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isDocumentDeleted(): bool
    {
        return $this->getValue() === self::DOCUMENT_DELETED;
    }

    public function isDocumentAdded(): bool
    {
        return $this->getValue() === self::DOCUMENT_ADDED;
    }

    public function isFieldChanged(): bool
    {
        return $this->getValue() === self::FIELD_CHANGED;
    }

    public function isNewField(): bool
    {
        return $this->getValue() === self::NEW_FIELD;
    }

    public function isRemovedField(): bool
    {
        return $this->getValue() === self::REMOVED_FIELD;
    }

    public function isArrayValueChanged(): bool
    {
        return $this->getValue() === self::ARRAY_VALUE_CHANGED;
    }

    public function isArrayValueAdded(): bool
    {
        return $this->getValue() === self::ARRAY_VALUE_ADDED;
    }

    public function isArrayValueRemoved(): bool
    {
        return $this->getValue() === self::ARRAY_VALUE_REMOVED;
    }

    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * @param string $value
     * @throws InvalidValueException
     */
    public function validateValue(string $value): void
    {
        if (!self::exists($value)) {
            throw new InvalidValueException(self::NAME, $value);
        }
    }

    public static function allValues(): array
    {
        return [
            self::DOCUMENT_DELETED,
            self::DOCUMENT_ADDED,
            self::FIELD_CHANGED,
            self::NEW_FIELD,
            self::REMOVED_FIELD,
            self::ARRAY_VALUE_CHANGED,
            self::ARRAY_VALUE_ADDED,
            self::ARRAY_VALUE_REMOVED,
            self::UNKNOWN
        ];
    }

    public static function exists(string $value): bool
    {
        return in_array($value, self::allValues());
    }

    public static function unknown(): self
    {
        return new ChangeType(ChangeType::UNKNOWN);
    }

    public static function documentDeleted(): self
    {
        return new ChangeType(ChangeType::DOCUMENT_DELETED);
    }

    public static function documentAdded(): self
    {
        return new ChangeType(ChangeType::DOCUMENT_ADDED);
    }

    public static function fieldChanged(): self
    {
        return new ChangeType(ChangeType::FIELD_CHANGED);
    }

    public static function newField(): self
    {
        return new ChangeType(ChangeType::NEW_FIELD);
    }

    public static function removedField(): self
    {
        return new ChangeType(ChangeType::REMOVED_FIELD);
    }

    public static function arrayValueChanged(): self
    {
        return new ChangeType(ChangeType::ARRAY_VALUE_CHANGED);
    }

    public static function arrayValueAdded(): self
    {
        return new ChangeType(ChangeType::ARRAY_VALUE_ADDED);
    }

    public static function arrayValueRemoved(): self
    {
        return new ChangeType(ChangeType::ARRAY_VALUE_REMOVED);
    }
}
