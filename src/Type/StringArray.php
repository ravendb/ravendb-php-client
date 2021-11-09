<?php

namespace RavenDB\Type;

class StringArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct('string');
    }
}
