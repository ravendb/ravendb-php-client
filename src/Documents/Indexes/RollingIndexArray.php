<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedArray;

// !status: DONE
class RollingIndexArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(RollingIndex::class);
    }

    public static function fromArray($array): RollingIndexArray
    {
        $a = new RollingIndexArray();

        foreach ($array as $key => $item) {
            $a[$key] = $item;
        }

        return $a;
    }
}
