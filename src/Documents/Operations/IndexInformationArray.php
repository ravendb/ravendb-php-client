<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Type\TypedArray;

// !status: DONE
class IndexInformationArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(IndexInformation::class);
    }

    public static function fromArray(array $items): IndexInformationArray
    {
        $i = new IndexInformationArray();
        foreach ($items as $item) {
            $i->append($item);
        }
        return $i;
    }
}
