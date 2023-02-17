<?php

namespace tests\RavenDB\Test\Client\Indexing\TimeSeries\_BasicTimeSeriesIndexes_StrongSyntaxTest;

use RavenDB\Documents\Indexes\TimeSeries\AbstractTimeSeriesIndexCreationTask;

class MyTsIndex extends AbstractTimeSeriesIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "from ts in timeSeries.Companies.HeartRate " .
            "from entry in ts.Entries " .
            "select new { " .
            "   heartBeat = entry.Values[0], " .
            "   date = entry.Timestamp.Date, " .
            "   user = ts.DocumentId " .
            "}";
    }
}
