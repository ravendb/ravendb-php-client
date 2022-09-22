<?php

namespace RavenDB\Type;

class StringList extends StringArray
{
    public static function fromArray(array $data, $nullAllowed = false): StringList
    {
        $sa = new StringList();

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
