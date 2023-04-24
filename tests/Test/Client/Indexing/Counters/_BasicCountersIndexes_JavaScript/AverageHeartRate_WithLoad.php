<?php

namespace tests\RavenDB\Test\Client\Indexing\Counters\_BasicCountersIndexes_JavaScript;

use RavenDB\Documents\Indexes\Counters\AbstractJavaScriptCountersIndexCreationTask;

class AverageHeartRate_WithLoad extends AbstractJavaScriptCountersIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->setMaps(["counters.map('Users', 'heartRate', function (counter) {\n" .
            "var user = load(counter.DocumentId, 'Users');\n" .
            "var address = load(user.addressId, 'Addresses');\n" .
            "return {\n" .
            "    heartBeat: counter.Value,\n" .
            "    count: 1,\n" .
            "    city: address.city\n" .
            "};\n" .
            "})"]);

        $this->setReduce("groupBy(r => ({ city: r.city }))\n" .
            " .aggregate(g => ({\n" .
            "     heartBeat: g.values.reduce((total, val) => val.heartBeat + total, 0) / g.values.reduce((total, val) => val.count + total, 0),\n" .
            "     city: g.key.city,\n" .
            "     count: g.values.reduce((total, val) => val.count + total, 0)\n" .
            " }))");
    }
}
