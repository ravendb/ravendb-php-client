<?php

namespace RavenDB\Type;

class ObjectArray extends \ArrayObject
{
    public static function withValue(object $id): ObjectArray
    {
        $a = new ObjectArray();
        $a->append($id);
        return $a;
    }

    public static function fromArray(array $data): ObjectArray
    {
        $sa = new ObjectArray();

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }

    public function offsetSet($key, $value)
    {
        if (!is_object($value)) {
            throw new \TypeError("Only object as values are supported");
        }

        parent::offsetSet($key, $value);
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
