<?php

namespace tests\RavenDB\Test\Issues\RavenDB_12030Test;

use RavenDB\Documents\Indexes\FieldIndexing;
use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class Fox_Search extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "from f in docs.Foxes select new { f.name }";

        $this->index("name", FieldIndexing::search());
    }
}
