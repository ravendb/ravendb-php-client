<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Type\TypedArray;

class DatabaseAccessArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DatabaseAccess::class);
    }

    public static function fromArray(array $data): DatabaseAccessArray
    {
        $dba = new DatabaseAccessArray();

        foreach ($data as $key => $value) {
            $dba->offsetSet($key, new DatabaseAccess($value));
        }

        return $dba;
    }
}
