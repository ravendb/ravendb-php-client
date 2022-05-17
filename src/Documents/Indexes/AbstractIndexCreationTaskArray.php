<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\TypedArray;

// !status: DONE
class AbstractIndexCreationTaskArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(AbstractIndexCreationTask::class);
    }

    public static function fromArray(array $items): AbstractIndexCreationTaskArray
    {
        $array = new AbstractIndexCreationTaskArray();

        foreach ($items as $item) {
            $array->append($item);
        }

        return $array;
    }
}
