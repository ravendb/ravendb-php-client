<?php

namespace RavenDB\Documents\Attachments;

use RavenDB\Type\ValueObjectInterface;

class AttachmentType implements ValueObjectInterface
{
    private const DOCUMENT = 'Document';
    private const REVISION = 'Revision';

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

    public function isDocument(): bool
    {
        return $this->value == self::DOCUMENT;
    }

    public static function document(): AttachmentType
    {
        return new AttachmentType(self::DOCUMENT);
    }

    public function isRevision(): bool
    {
        return $this->value == self::REVISION;
    }

    public static function revision(): AttachmentType
    {
        return new AttachmentType(self::REVISION);
    }
}
