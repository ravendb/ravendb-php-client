<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14084Test;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Type\StringSet;

class Companies_ByUnknown extends AbstractIndexCreationTask
{
    public function createIndexDefinition(): IndexDefinition
    {
            $indexDefinition = new IndexDefinition();
            $indexDefinition->setName("Companies/ByUnknown");
            $indexDefinition->setMaps(StringSet::fromArray(["from c in docs.Companies select new { Unknown = c.Unknown };"]));
            return $indexDefinition;
    }
}
