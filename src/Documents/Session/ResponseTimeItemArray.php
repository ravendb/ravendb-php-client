<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedList;

class ResponseTimeItemArray extends TypedList
{
    public function __construct()
    {
        parent::__construct(ResponseTimeItem::class);
    }
}
