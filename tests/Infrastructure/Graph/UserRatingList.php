<?php

namespace tests\RavenDB\Infrastructure\Graph;

use RavenDB\Type\TypedList;

class UserRatingList extends TypedList
{
    public function __construct()
    {
        parent::__construct(UserRating::class);
    }

    public static function fromArray(array $data): UserRatingList
    {
        $array = new UserRatingList();
        foreach ($data as $item) {
            $array->append($item);
        }
        return $array;
    }
}
