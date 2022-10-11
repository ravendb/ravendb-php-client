<?php

namespace tests\RavenDB\Test\Issues\RavenDB_16328_AnalyzersTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldIndexing;

class MyIndex_WithoutAnalyzer extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "from customer in docs.Customers select new { customer.name }";
        $this->index("name", FieldIndexing::search());
    }
}
