<?php

namespace RavenDB\Http;

use RavenDB\Type\TypedArray;

class ItemFlagsSet extends TypedArray
{
    public function __construct()
    {
        parent::__construct(ItemFlags::class);
    }

    public function contains(ItemFlags $flag): bool
    {
        /** @var ItemFlags $item */
        foreach ($this as $item) {
            if ($item->isEqual($flag)) {
                return true;
            }
        }
        return false;
    }
}
