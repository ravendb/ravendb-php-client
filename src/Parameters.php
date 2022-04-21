<?php

namespace RavenDB;

use RavenDB\Type\ExtendedArrayObject;

class Parameters extends ExtendedArrayObject
{
    public function __construct($array = [])
    {
        parent::__construct($array);
    }
}
