<?php

namespace RavenDB\Http;

use RavenDB\Type\TypedArray;

class ServerNodeList extends TypedArray
{
    public function __construct()
    {
        parent::__construct(ServerNode::class);
    }
}
