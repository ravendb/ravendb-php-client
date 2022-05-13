<?php

namespace RavenDB\Type;

use RavenDB\Http\ResultInterface;

class StringArrayResult extends StringArray implements ResultInterface
{
    public static function fromArray(array $data): StringArrayResult
    {
        $sa = new StringArrayResult();

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
