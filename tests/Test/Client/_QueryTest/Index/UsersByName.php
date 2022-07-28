<?php

namespace tests\RavenDB\Test\Client\_QueryTest\Index;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class UsersByName extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "from c in docs.Users select new " .
                " {" .
                "    c.name, " .
                "    count = 1" .
                "}";

        $this->reduce = "from result in results " .
                "group result by result.name " .
                "into g " .
                "select new " .
                "{ " .
                "  name = g.Key, " .
                "  count = g.Sum(x => x.count) " .
                "}";
        }
}
