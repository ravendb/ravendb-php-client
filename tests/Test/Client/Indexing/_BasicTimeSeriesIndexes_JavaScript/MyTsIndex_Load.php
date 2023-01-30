<?php

namespace tests\RavenDB\Test\Client\Indexing\_BasicTimeSeriesIndexes_JavaScript;

use RavenDB\Documents\Indexes\TimeSeries\AbstractJavaScriptTimeSeriesIndexCreationTask;

class MyTsIndex_Load extends AbstractJavaScriptTimeSeriesIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->setMaps(["timeSeries.map('Companies', 'HeartRate', function (ts) {\n" .
            "return ts.Entries.map(entry => ({\n" .
            "        heartBeat: entry.Value,\n" .
            "        date: new Date(entry.Timestamp.getFullYear(), entry.Timestamp.getMonth(), entry.Timestamp.getDate()),\n" .
            "        user: ts.DocumentId,\n" .
            "        employee: load(entry.Tag, 'Employees').firstName\n" .
            "    }));\n" .
            "})"]);
    }
}
