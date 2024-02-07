<?php

namespace RavenDB\Documents\Operations\Etl\ElasticSearch;

use RavenDB\Type\TypedList;

class ElasticSearchIndexList extends TypedList
{
    public function __construct()
    {
        parent::__construct(ElasticSearchIndex::class);
    }
}
