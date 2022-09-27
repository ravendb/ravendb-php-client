<?php

namespace tests\RavenDB\Test\Issues\RavenDB_9745Test;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldStorage;

class Companies_ByName extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "from c in docs.Companies select new { key = c.name, count = 1 }";

        $this->reduce = "from result in results " .
            "group result by result.key " .
            "into g " .
            "select new " .
            "{ " .
            "  key = g.Key, " .
            "  count = g.Sum(x => x.count) " .
            "}";

        $this->store("key", FieldStorage::yes());
    }
}
