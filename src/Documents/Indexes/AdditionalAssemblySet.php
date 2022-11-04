<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedSet;

class AdditionalAssemblySet extends TypedSet
{
    public function __construct()
    {
        parent::__construct(AdditionalAssembly::class);
    }

    public static function fromArray(array $data, $nullAllowed = false): AdditionalAssemblySet
    {
        $sa = new AdditionalAssemblySet();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
