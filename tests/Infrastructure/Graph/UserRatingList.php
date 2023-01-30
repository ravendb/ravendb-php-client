<?php

namespace tests\RavenDB\Infrastructure\Graph;

use RavenDB\Type\TypedList;

class UserRatingList extends TypedList
{
    public function __construct()
    {
        parent::__construct(UserRating::class);
    }
}
