<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Type\ValueObjectInterface;

class ProjectionBehavior implements ValueObjectInterface
{
    /**
     * Try to extract value from index field (if field value is stored in index),
     * on a failure (or when field value is not stored in index) extract value from a document
     */
    private const DEFAULT = 'Default';

    /**
     * Try to extract value from index field (if field value is stored in index), on a failure skip field
     */
    private const FROM_INDEX = 'FromIndex';

    /**
     * Extract value from index field or throw
     */
    private const FROM_INDEX_OR_THROW = 'FromIndexOrThrow';

    /**
     * Try to extract value from document field, on a failure skip field
     */
    private const FROM_DOCUMENT = 'FromDocument';

    /**
     * Extract value from document field or throw
     */
    private const FROM_DOCUMENT_OR_THROW = 'FromDocumentOThrow';

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

    public function isDefault(): bool
    {
        return $this->value == self::DEFAULT;
    }

    public function isFromIndex(): bool
    {
        return $this->value == self::FROM_INDEX;
    }

    public function isFromIndexOrThrow(): bool
    {
        return $this->value == self::FROM_INDEX_OR_THROW;
    }

    public function isFromDocument(): bool
    {
        return $this->value == self::FROM_DOCUMENT;
    }

    public function isFromDocumentOrThrow(): bool
    {
        return $this->value == self::FROM_DOCUMENT_OR_THROW;
    }

    public static function default(): ProjectionBehavior
    {
        return new ProjectionBehavior(self::DEFAULT);
    }

    public static function fromIndex(): ProjectionBehavior
    {
        return new ProjectionBehavior(self::FROM_INDEX);
    }

    public static function fromIndexOrThrow(): ProjectionBehavior
    {
        return new ProjectionBehavior(self::FROM_INDEX_OR_THROW);
    }

    public static function fromDocument(): ProjectionBehavior
    {
        return new ProjectionBehavior(self::FROM_DOCUMENT);
    }

    public static function fromDocumentOrThrow(): ProjectionBehavior
    {
        return new ProjectionBehavior(self::FROM_DOCUMENT_OR_THROW);
    }
}
