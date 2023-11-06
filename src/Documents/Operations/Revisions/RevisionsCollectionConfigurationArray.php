<?php

namespace RavenDB\Documents\Operations\Revisions;

use RavenDB\Type\TypedArray;

class RevisionsCollectionConfigurationArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(RevisionsCollectionConfiguration::class);
    }
}
