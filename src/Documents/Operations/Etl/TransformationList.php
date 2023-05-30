<?php

namespace RavenDB\Documents\Operations\Etl;

use RavenDB\Type\TypedList;

class TransformationList extends TypedList
{
    public function __construct()
    {
        parent::__construct(Transformation::class);
    }
}
