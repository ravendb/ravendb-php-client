<?php

namespace RavenDB\Type;

use tests\RavenDB\Test\Issues\RavenDB_14084Test\Companies_ByUnknown_WithIndexMissingFieldsAsNull;

class TypedArray extends ExtendedArrayObject implements TypedArrayInterface
{
    protected string $type;
    protected bool $nullAllowed = false;

    protected function __construct(string $type)
    {
        $this->type = $type;

        if (!class_exists($type) && !interface_exists($type)) {
            throw new \TypeError(
                sprintf("Typed array cant be instantiated. Class or interface: >> %s <<  does not exists! ", $this->type)
            );
        }

        parent::__construct();
    }

    public static function forType(string $type): self
    {
        return new self($type);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function allowNull(): void
    {
        $this->nullAllowed = true;
    }

    public function disallowNull(): void
    {
        $this->nullAllowed = false;
    }

    public function isNullAllowed(): bool
    {
        return $this->nullAllowed;
    }

    protected function validateValue($value): void
    {
        if ($value == null) {
            if (!$this->isNullAllowed()) {
                throw new \TypeError('Null is not allowed to be added as value');
            }
        }

        if (!($value instanceof $this->type) && ($value != null)) {
            throw new \TypeError(
                sprintf("Only values of type %s are supported", $this->type)
            );
        }
    }
}
