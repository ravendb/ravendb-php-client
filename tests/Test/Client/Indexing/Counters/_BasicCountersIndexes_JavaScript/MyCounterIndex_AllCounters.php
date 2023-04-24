<?php

namespace tests\RavenDB\Test\Client\Indexing\Counters\_BasicCountersIndexes_JavaScript;

use RavenDB\Documents\Indexes\Counters\AbstractJavaScriptCountersIndexCreationTask;

class MyCounterIndex_AllCounters extends AbstractJavaScriptCountersIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->setMaps(["counters.map('Companies', function (counter) {\n" .
            "return {\n" .
            "    heartBeat: counter.Value,\n" .
            "    name: counter.Name,\n" .
            "    user: counter.DocumentId\n" .
            "};\n" .
            "})"]);
    }
}
