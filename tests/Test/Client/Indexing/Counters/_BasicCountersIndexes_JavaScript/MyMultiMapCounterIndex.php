<?php

namespace tests\RavenDB\Test\Client\Indexing\Counters\_BasicCountersIndexes_JavaScript;

use RavenDB\Documents\Indexes\Counters\AbstractJavaScriptCountersIndexCreationTask;

class MyMultiMapCounterIndex extends AbstractJavaScriptCountersIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $maps = [];

        $maps[] = "counters.map('Companies', 'heartRate', function (counter) {\n" .
            "return {\n" .
            "    heartBeat: counter.Value,\n" .
            "    name: counter.Name,\n" .
            "    user: counter.DocumentId\n" .
            "};\n" .
            "})";

        $maps[] = "counters.map('Companies', 'heartRate2', function (counter) {\n" .
            "return {\n" .
            "    heartBeat: counter.Value,\n" .
            "    name: counter.Name,\n" .
            "    user: counter.DocumentId\n" .
            "};\n" .
            "})";

        $maps[] = "counters.map('Users', 'heartRate', function (counter) {\n" .
            "return {\n" .
            "    heartBeat: counter.Value,\n" .
            "    name: counter.Name,\n" .
            "    user: counter.DocumentId\n" .
            "};\n" .
            "})";

        $this->setMaps($maps);
    }
}
