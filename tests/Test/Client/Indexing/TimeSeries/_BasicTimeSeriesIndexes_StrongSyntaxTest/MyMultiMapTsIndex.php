<?php

namespace tests\RavenDB\Test\Client\Indexing\TimeSeries\_BasicTimeSeriesIndexes_StrongSyntaxTest;

use RavenDB\Documents\Indexes\TimeSeries\AbstractMultiMapTimeSeriesIndexCreationTask;

class MyMultiMapTsIndex extends AbstractMultiMapTimeSeriesIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->addMap("from ts in timeSeries.Companies.HeartRate " .
            "from entry in ts.Entries " .
            "select new { " .
            "   heartBeat = entry.Values[0], " .
            "   date = entry.Timestamp.Date, " .
            "   user = ts.DocumentId " .
            "}");

        $this->addMap("from ts in timeSeries.Companies.HeartRate2 " .
            "from entry in ts.Entries " .
            "select new { " .
            "   heartBeat = entry.Values[0], " .
            "   date = entry.Timestamp.Date, " .
            "   user = ts.DocumentId " .
            "}");

        $this->addMap("from ts in timeSeries.Users.HeartRate " .
            "from entry in ts.Entries " .
            "select new { " .
            "   heartBeat = entry.Values[0], " .
            "   date = entry.Timestamp.Date, " .
            "   user = ts.DocumentId " .
            "}");
    }
}
