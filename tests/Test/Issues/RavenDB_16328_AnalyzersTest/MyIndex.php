<?php

namespace tests\RavenDB\Test\Issues\RavenDB_16328_AnalyzersTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldIndexing;

class MyIndex extends AbstractIndexCreationTask
{
    public function __construct(?string $analyzerName = null)
    {
        parent::__construct();

        if (empty($analyzerName)) {
            $analyzerName = "MyAnalyzer";
        }

        $this->map = "from customer in docs.Customers select new { customer.name }";

        $this->index("name", FieldIndexing::search());
        $this->analyze("name", $analyzerName);
    }
}
