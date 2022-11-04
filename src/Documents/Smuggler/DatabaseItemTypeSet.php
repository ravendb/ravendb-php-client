<?php

namespace RavenDB\Documents\Smuggler;

use RavenDB\Type\TypedSet;

// !status: DONE
class DatabaseItemTypeSet extends TypedSet
{
    public function __construct()
    {
        parent::__construct(DatabaseItemType::class);
    }

    public static function fromArray(array $items, bool $nullAllowed = false): DatabaseItemTypeSet
    {
        $d = new DatabaseItemTypeSet();
        $d->setNullAllowed($nullAllowed);

        foreach ($items as $key => $value) {
            $d->offsetSet($key, $value);
        }

        return $d;
    }
}
