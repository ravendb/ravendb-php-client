<?php

namespace RavenDB\Documents\Commands\MultiGet;

use RavenDB\Type\TypedList;

class GetRequestList extends TypedList
{
    public function __construct()
    {
        parent::__construct(GetRequest::class);
    }
}
