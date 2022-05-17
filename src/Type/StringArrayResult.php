<?php

namespace RavenDB\Type;

use RavenDB\Http\ResultInterface;

class StringArrayResult extends StringArray implements ResultInterface
{
    public static function fromArray(array $data): StringArrayResult
    {
        $array = new StringArrayResult();

        foreach ($data as $key => $value) {
            $array->offsetSet($key, $value);
        }

        return $array;
    }
}
