<?php

namespace RavenDB\Type;

class ObjectArray extends ExtendedArrayObject
{
    public static function withValue(object $id): ObjectArray
    {
        $a = new ObjectArray();
        $a->append($id);
        return $a;
    }


    public function offsetSet($key, $value): void
    {
        if (!is_object($value) && ($value != null)) {
            throw new \TypeError("Only object as values are supported");
        }

        parent::offsetSet($key, $value);
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
