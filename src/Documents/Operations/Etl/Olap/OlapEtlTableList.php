<?php

namespace RavenDB\Documents\Operations\Etl\Olap;

use RavenDB\Type\TypedList;

class OlapEtlTableList extends TypedList
{
    public function __construct()
    {
        parent::__construct(OlapEtlTable::class);
    }
}
