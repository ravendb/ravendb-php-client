<?php

namespace RavenDB\Type;

class Collection extends ExtendedArrayObject
{

    public static function fromArray(array $data): Collection
    {
        $sa = new Collection();

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
