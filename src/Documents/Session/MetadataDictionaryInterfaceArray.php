<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedArray;

class MetadataDictionaryInterfaceArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(MetadataDictionaryInterface::class);
    }
}
