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

    public static function fromArray(array $data, $nullAllowed = false): AbstractIndexCreationTaskArray
    {
        $sa = new AbstractIndexCreationTaskArray();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
