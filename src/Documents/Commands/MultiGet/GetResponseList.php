<?php

namespace RavenDB\Documents\Commands\MultiGet;

use RavenDB\Type\TypedList;

class GetResponseList extends TypedList
{
    public function __construct()
    {
        parent::__construct(GetResponse::class);
    }
}
