<?php

namespace RavenDB\Http;

use RavenDB\Type\TypedArray;

class ServerNodeArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(ServerNode::class);
    }
}
