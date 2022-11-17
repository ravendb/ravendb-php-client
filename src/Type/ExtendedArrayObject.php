<?php

namespace RavenDB\Type;

class ExtendedArrayObject extends \ArrayObject implements \JsonSerializable
{
    protected bool $nullAllowed = false;

    public function setNullAllowed(bool $nullAllowed): void
    {
        $this->nullAllowed = $nullAllowed;
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
        if (!$this->isValueValid($value)) {
            throw new \TypeError($this->getInvalidValueMessage($value));
        }
    }

    protected function isValueValid($value): bool
    {
        return true;
    }

    protected function getInvalidValueMessage($value): string
    {
        return 'This value is not allowed.';
    }

    public function offsetSet($key, $value): void
    {
        $this->validateValue($value);
        parent::offsetSet($key, $value);
    }

    public function prepend($value): void
    {
        $this->validateValue($value);

        $currentArray = $this->getArrayCopy();
        array_unshift($currentArray, $value);

        $this->exchangeArray($currentArray);
    }

    public function appendArrayValues(array $items): void
    {
        foreach ($items as $item) {
            $this->append($item);
        }
    }

    public function containsValue($value): bool
    {
        return in_array($value, $this->getArrayCopy(), true);
    }

    public function removeValue($value): void
    {
        if(($key = array_search($value, $this->getArrayCopy(), true)) !== FALSE) {
            $this->offsetUnset($key);
        }
    }

    public function clear(): void
    {
        foreach ($this as $key => $value) {
            $this->offsetUnset($key);
        }
    }

    public function isEmpty(): bool
    {
        return $this->count() == 0;
    }

    public function isNotEmpty(): bool
    {
        return $this->count() > 0;
    }

    public function first()
    {
        return $this->offsetGet(array_key_first($this->getArrayCopy()));
    }

    public function last()
    {
        return $this->offsetGet(array_key_last($this->getArrayCopy()));
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
