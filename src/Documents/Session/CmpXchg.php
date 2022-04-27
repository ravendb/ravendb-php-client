<?php

namespace RavenDB\Documents\Session;

class CmpXchg extends MethodCall
{
    public static function value(string $key):  CmpXchg
    {
        $cmpXchg = new CmpXchg();
        $cmpXchg->args = [$key];

    return $cmpXchg;
    }
}
