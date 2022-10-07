<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14600Test;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class MyIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();
        $this->map = "from o in docs.Orders select new { o.employee, o.company }";
    }
}
