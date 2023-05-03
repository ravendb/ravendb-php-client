<?php

namespace tests\RavenDB\Test\Client\MoreLikeThis\_MoreLikeThisTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldIndexing;

class ComplexDataIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "from doc in docs.ComplexDatas select new  { doc.property, doc.property.body }";

        $this->index("body", FieldIndexing::search());
    }
}
