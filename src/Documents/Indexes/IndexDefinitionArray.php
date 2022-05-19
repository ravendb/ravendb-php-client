<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Http\ResultInterface;
use RavenDB\Type\TypedArray;

// !status: DONE
class IndexDefinitionArray extends TypedArray implements ResultInterface
{
    public function __construct()
    {
        parent::__construct(IndexDefinition::class);
    }

    public static function fromArray(array $data): IndexDefinitionArray
    {
        $array = new IndexDefinitionArray();
        foreach ($data as $item) {
            $array->append($item);
        }
        return $array;
    }
}
