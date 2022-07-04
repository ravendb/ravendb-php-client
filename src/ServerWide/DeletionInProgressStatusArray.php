<?php

namespace RavenDB\ServerWide;

use RavenDB\Type\TypedArray;

// !status = DONE
class DeletionInProgressStatusArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DeletionInProgressStatus::class);
    }

    public static function fromArray(array $array): DeletionInProgressStatusArray
    {
        $a = new DeletionInProgressStatusArray();

        foreach ($array as $key => $item) {
            $a[$key] = $item;
        }

        return $a;
    }
}
