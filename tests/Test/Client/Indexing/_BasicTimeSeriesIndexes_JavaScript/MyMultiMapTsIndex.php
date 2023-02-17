<?php

namespace tests\RavenDB\Test\Client\Indexing\_BasicTimeSeriesIndexes_JavaScript;

use RavenDB\Documents\Indexes\TimeSeries\AbstractJavaScriptTimeSeriesIndexCreationTask;

class MyMultiMapTsIndex extends AbstractJavaScriptTimeSeriesIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $maps = [];

        $maps[] = "timeSeries.map('Companies', 'HeartRate', function (ts) {\n" .
            "return ts.Entries.map(entry => ({\n" .
            "        HeartBeat: entry.Values[0],\n" .
            "        Date: new Date(entry.Timestamp.getFullYear(), entry.Timestamp.getMonth(), entry.Timestamp.getDate()),\n" .
            "        User: ts.DocumentId\n" .
            "    }));\n" .
            "})";

        $maps[] = "timeSeries.map('Companies', 'HeartRate2', function (ts) {\n" .
            "return ts.Entries.map(entry => ({\n" .
            "        HeartBeat: entry.Values[0],\n" .
            "        Date: new Date(entry.Timestamp.getFullYear(), entry.Timestamp.getMonth(), entry.Timestamp.getDate()),\n" .
            "        User: ts.DocumentId\n" .
            "    }));\n" .
            "})";

        $maps[] = "timeSeries.map('Users', 'HeartRate', function (ts) {\n" .
            "return ts.Entries.map(entry => ({\n" .
            "        HeartBeat: entry.Values[0],\n" .
            "        Date: new Date(entry.Timestamp.getFullYear(), entry.Timestamp.getMonth(), entry.Timestamp.getDate()),\n" .
            "        User: ts.DocumentId\n" .
            "    }));\n" .
            "})";

        $this->setMaps($maps);
    }
}
