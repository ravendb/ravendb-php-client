<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesRawQueryTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class PeopleIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "from p in docs.People select new { p.age }";
    }

    public function getIndexName(): string
    {
        return "People";
    }
}
