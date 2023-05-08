<?php

namespace RavenDB\Type;

class ExtendedArrayObject extends \ArrayObject implements \JsonSerializable
{
    protected bool $nullAllowed = false;

    protected bool $keysCaseInsensitive = false;

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

    public function setKeysCaseInsensitive(bool $caseInsensitive): void
    {
        if ($caseInsensitive) {
            $arrayWithAllLowerCase = array_change_key_case($this->getArrayCopy(), CASE_LOWER);
            $this->exchangeArray($arrayWithAllLowerCase);
        }
        $this->keysCaseInsensitive = $caseInsensitive;
    }

    public function useKeysCaseInsensitive(): void
    {
        $this->keysCaseInsensitive = true;
    }

    public function useKeysCaseSensitive(): void
    {
        $this->keysCaseInsensitive = true;
    }

    public function isKeysCaseInsensitive(): bool
    {
        return $this->keysCaseInsensitive;
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

    private array $keyMap = [];

    public function offsetSet($key, $value): void
    {
        $this->validateValue($value);
        parent::offsetSet($key, $value);
        if ($key == null) {
            $key = array_key_last($this->getArrayCopy());
        }
        $this->keyMap[strtolower($key)] = $key;
    }

    public function offsetGet(mixed $key): mixed
    {
        return parent::offsetGet($this->key($key));
    }

    public function offsetExists($key): bool
    {
        if ($this->keysCaseInsensitive) {
            return array_key_exists(strtolower($key), $this->keyMap);
        }
        return parent::offsetExists($key);
    }

    public function offsetUnset($key): void
    {
        parent::offsetUnset($this->key($key));
        unset($this->keyMap[strtolower($key)]);
    }

    private function key($key): ?string
    {
        if (!$this->keysCaseInsensitive) {
            return $key;
        }

        return $this->keyMap[strtolower($key)];
    }

    public function prepend($value): void
    {
        $this->validateValue($value);

        $currentArray = $this->getArrayCopy();
        array_unshift($currentArray, $value);

        $this->exchangeArray($currentArray);
    }

    public function appendArrayValues(array|ExtendedArrayObject $items): void
    {
        foreach ($items as $item) {
            $this->append($item);
        }
    }

    public function insertValue($position, $value)
    {
        $this->insertValues($position, [ $value ]);
    }

    public function insertValues($position, $values)
    {
        $array = $this->getArrayCopy();

        if (is_int($position)) {
            array_splice($array, $position, 0, $values);
        } else {
            $pos = array_search($this->key($position), array_keys($array));
            $array = array_merge(
                array_slice($array, 0, $pos),
                $this->keysCaseInsensitive ? array_change_key_case($values, CASE_LOWER) : $values,
                array_slice($array, $pos)
            );
        }

        $this->exchangeArray($array);
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

    public function removeValues(int $start, int $count, $preserveKeys = false)
    {
        $a = $this->getArrayCopy();
        array_splice($a, $start, $count);
        if (!$preserveKeys) {
            $a = array_values($a);
        }
        $this->exchangeArray($a);
    }

    public function removeRange(int $from, int $to, bool $preserveKeys = false)
    {
        $this->removeValues($from, $to - $from);
    }

    public function clear(): void
    {
        foreach ($this->getArrayCopy() as $key => $value) {
            $this->offsetUnset($key);
        }
        $this->exchangeArray(array_values($this->getArrayCopy()));
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

    public function slice(int $offset, ?int $length = null, bool $preserveKeys = false): static
    {
        return static::fromArray(array_slice($this->getArrayCopy(), $offset, $length, $preserveKeys));
    }

    public function shift(): mixed
    {
        $a = $this->getArrayCopy();

        $removedItem = array_shift($a);
        $this->exchangeArray($a);

        return $removedItem;
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }

    public static function fromArray(array $data, bool $nullAllowed = false): static
    {
        $sa = new static();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }

    public static function ensure(mixed $data, bool $nullAllowed = false): static
    {
        if (is_null($data)) {
            return new static();
        }

        if (is_array($data)) {
            return static::fromArray($data, $nullAllowed);
        };

        if (is_a($data, static::class)) {
            return $data;
        }

        throw new \TypeError('Passed data must be of type array or subclass of '. static::class . ' class.');
    }
}
