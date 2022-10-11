<?php

namespace RavenDB\Http;

use RavenDB\Type\TypedArray;

class ServerNodeArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(ServerNode::class);
    }

    public static function fromArray(array $data, $nullAllowed = false): ServerNodeArray
    {
        $sa = new ServerNodeArray();

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
