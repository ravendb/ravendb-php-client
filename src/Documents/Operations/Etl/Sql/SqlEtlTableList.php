<?php

namespace RavenDB\Documents\Operations\Etl\Sql;

use RavenDB\Type\TypedList;

class SqlEtlTableList extends TypedList
{
    public function __construct()
    {
        parent::__construct(SqlEtlTable::class);
    }
}
