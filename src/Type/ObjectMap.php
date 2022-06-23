<?php

namespace RavenDB\Type;

class ObjectMap extends ExtendedArrayObject
{

    public static function fromArray(array $values): ObjectMap
    {
        $om = new ObjectMap();
        foreach ($values as $key => $item) {
            $om->offsetSet($key, $item);
        }
        return $om;
    }
}
