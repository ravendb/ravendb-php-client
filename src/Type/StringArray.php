<?php

namespace RavenDB\Type;

class StringArray extends ExtendedArrayObject implements \JsonSerializable
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function withValue(string $id): StringArray
    {
        $a = new StringArray();
        $a->append($id);
        return $a;
    }

    protected function isValueValid($value): bool
    {
        if ($this->isNullAllowed() && $value == null) {
            return true;
        }

        return is_string($value);
    }

    protected function getInvalidValueMessage($value): string
    {
        return 'Only values of type string are supported.';
    }

    public function isEmpty(): bool
    {
        return $this->count() == 0;
    }

    public function isNotEmpty(): bool
    {
        return $this->count() > 0;
    }

    public function hasValue(string $fieldName): bool
    {
        return in_array($fieldName, $this->getArrayCopy());
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }

    public function get(string $key): ?string
    {
        if ($this->offsetExists($key)) {
            return $this->offsetGet($key);
        }

        return null;
    }
}
