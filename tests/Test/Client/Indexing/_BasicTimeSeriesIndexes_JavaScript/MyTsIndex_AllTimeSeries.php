<?php

namespace tests\RavenDB\Test\Client\Indexing\_BasicTimeSeriesIndexes_JavaScript;

use RavenDB\Documents\Indexes\TimeSeries\AbstractJavaScriptTimeSeriesIndexCreationTask;

class MyTsIndex_AllTimeSeries extends AbstractJavaScriptTimeSeriesIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->setMaps(["timeSeries.map('Companies', function (ts) {\n" .
            " return ts.Entries.map(entry => ({\n" .
            "        heartBeat: entry.Values[0],\n" .
            "        date: new Date(entry.Timestamp.getFullYear(), entry.Timestamp.getMonth(), entry.Timestamp.getDate()),\n" .
            "        user: ts.documentId\n" .
            "    }));\n" .
            " })"]);
    }
}
