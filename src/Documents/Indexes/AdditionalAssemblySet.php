<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedSet;

class AdditionalAssemblySet extends TypedSet
{
    public function __construct()
    {
        parent::__construct(AdditionalAssembly::class);
    }

    public static function fromArray(array $items): AdditionalAssemblySet
    {
        $set = new AdditionalAssemblySet();

        foreach ($items as $item) {
            $set->append($item);
        }

        return $set;
    }
}
