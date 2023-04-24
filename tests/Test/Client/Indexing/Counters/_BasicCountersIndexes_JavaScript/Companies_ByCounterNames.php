<?php

namespace tests\RavenDB\Test\Client\Indexing\Counters\_BasicCountersIndexes_JavaScript;

use RavenDB\Documents\Indexes\AbstractJavaScriptIndexCreationTask;

class Companies_ByCounterNames extends AbstractJavaScriptIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->setMaps(["map('Companies', function (company) {\n" .
            "return ({\n" .
            "    names: counterNamesFor(company)\n" .
            "})\n" .
            "})"]);
    }
}
