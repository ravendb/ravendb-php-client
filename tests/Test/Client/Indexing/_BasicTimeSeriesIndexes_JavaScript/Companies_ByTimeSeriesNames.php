<?php

namespace tests\RavenDB\Test\Client\Indexing\_BasicTimeSeriesIndexes_JavaScript;

use RavenDB\Documents\Indexes\AbstractJavaScriptIndexCreationTask;

class Companies_ByTimeSeriesNames extends AbstractJavaScriptIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();
        $this->setMaps(["map('Companies', function (company) {\n" .
            "return ({\n" .
            "    names: timeSeriesNamesFor(company)\n" .
            "})\n" .
            "})"]);
    }
}
