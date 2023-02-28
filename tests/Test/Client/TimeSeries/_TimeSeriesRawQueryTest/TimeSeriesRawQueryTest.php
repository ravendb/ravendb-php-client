<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesRawQueryTest;

use DateTime;
use RavenDB\Documents\Queries\TimeSeries\TimeSeriesRawResult;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\RemoteTestBase;

class TimeSeriesRawQueryTest extends RemoteTestBase
{
    public function testCanQueryTimeSeriesAggregation_DeclareSyntax_WithOtherFields(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                for ($i = 0; $i <= 3; $i++) {
                    $id = "people/" . $i;

                    $person = new Person();
                    $person->setName("Oren");
                    $person->setAge($i * 30);

                    $session->store($person, $id);

                    $tsf = $session->timeSeriesFor($id, "Heartrate");

                    $tsf->append(DateUtils::addMinutes($baseLine, 61), 59, "watches/fitbit");
                    $tsf->append(DateUtils::addMinutes($baseLine, 62), 79, "watches/fitbit");
                    $tsf->append(DateUtils::addMinutes($baseLine, 63), 69, "watches/fitbit");

                    $session->saveChanges();
                }
            } finally {
                $session->close();
            }

            (new PeopleIndex())->execute($store);

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $query = $session->advanced()->rawQuery(RawQueryResult::class, "declare timeseries out(p)\n" .
                        "{\n" .
                        "    from p.HeartRate between \$start and \$end\n" .
                        "    group by 1h\n" .
                        "    select min(), max()\n" .
                        "}\n" .
                        "from index 'People' as p\n" .
                        "where p.age > 49\n" .
                        "select out(p) as heartRate, p.name")
                        ->addParameter("start", $baseLine)
                        ->addParameter("end", DateUtils::addDays($baseLine, 1));

                $result = $query->toList();

                $this->assertCount(2, $result);

                for ($i = 0; $i < 2; $i++) {
                    $agg = $result[$i];

                    $this->assertEquals("Oren", $agg->getName());

                    $heartrate = $agg->getHeartRate();

                    $this->assertEquals(3, $heartrate->getCount());

                    $this->assertCount(1, $heartrate->getResults());

                    $val = $heartrate->getResults()[0];

                    $this->assertEquals(59, $val->getMin()[0]);
                    $this->assertEquals(79, $val->getMax()[0]);

                    $this->assertEquals(DateUtils::addMinutes($baseLine, 60), $val->getFrom());
                    $this->assertEquals(DateUtils::addMinutes($baseLine, 120), $val->getTo());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanQueryTimeSeriesAggregation_DeclareSyntax_MultipleSeries(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());
            $baseLine2 = DateUtils::addDays($baseLine, -1);

            $session = $store->openSession();
            try {
                for ($i = 0; $i <= 3; $i++) {
                    $id = "people/" . $i;

                    $person = new Person();
                    $person->setName("Oren");
                    $person->setAge($i * 30);

                    $session->store($person, $id);

                    $tsf = $session->timeSeriesFor($id, "Heartrate");

                    $tsf->append(DateUtils::addMinutes($baseLine, 61), 59, "watches/fitbit");
                    $tsf->append(DateUtils::addMinutes($baseLine, 62), 79, "watches/fitbit");
                    $tsf->append(DateUtils::addMinutes($baseLine, 63), 69, "watches/fitbit");

                    $tsf = $session->timeSeriesFor($id, "BloodPressure");

                    $tsf->append(DateUtils::addMinutes($baseLine2, 61), 159, "watches/apple");
                    $tsf->append(DateUtils::addMinutes($baseLine2, 62), 179, "watches/apple");
                    $tsf->append(DateUtils::addMinutes($baseLine2, 63), 168, "watches/apple");

                    $session->saveChanges();
                }
            } finally {
                $session->close();
            }

            (new PeopleIndex())->execute($store);

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $query = $session->advanced()->rawQuery(RawQueryResult::class, "declare timeseries heart_rate(doc)\n" .
                        "{\n" .
                        "    from doc.HeartRate between \$start and \$end\n" .
                        "    group by 1h\n" .
                        "    select min(), max()\n" .
                        "}\n" .
                        "declare timeseries blood_pressure(doc)\n" .
                        "{\n" .
                        "    from doc.BloodPressure between \$start2 and \$end2\n" .
                        "    group by 1h\n" .
                        "    select min(), max(), avg()\n" .
                        "}\n" .
                        "from index 'People' as p\n" .
                        "where p.age > 49\n" .
                        "select heart_rate(p) as heartRate, blood_pressure(p) as bloodPressure")
                        ->addParameter("start", $baseLine)
                        ->addParameter("end", DateUtils::addDays($baseLine, 1))
                        ->addParameter("start2", $baseLine2)
                        ->addParameter("end2", DateUtils::addDays($baseLine2, 1));

                $result = $query->toList();

                $this->assertCount(2, $result);

                for ($i = 0; $i < 2; $i++) {
                    $agg = $result[$i];

                    $heartRate = $agg->getHeartRate();
                    $this->assertEquals(3, $heartRate->getCount());
                    $this->assertCount(1, $heartRate->getResults());

                    $val = $heartRate->getResults()[0];

                    $this->assertEquals(59, $val->getMin()[0]);
                    $this->assertEquals(79, $val->getMax()[0]);

                    $this->assertEquals(DateUtils::addMinutes($baseLine, 60), $val->getFrom());
                    $this->assertEquals(DateUtils::addMinutes($baseLine, 120), $val->getTo());

                    $bloodPressure = $agg->getBloodPressure();

                    $this->assertEquals(3, $bloodPressure->getCount());

                    $this->assertCount(1, $bloodPressure->getResults());

                    $val = $bloodPressure->getResults()[0];

                    $this->assertEquals(159, $val->getMin()[0]);
                    $this->assertEquals(179, $val->getMax()[0]);

                    $expectedAvg = (159 +  168 + 179) / 3.0;

                    $this->assertEquals($expectedAvg, $val->getAverage()[0]);

                    $this->assertEquals(DateUtils::addMinutes($baseLine2, 60), $val->getFrom());
                    $this->assertEquals(DateUtils::addMinutes($baseLine2, 120), $val->getTo());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanQueryTimeSeriesAggregation_NoSelectOrGroupBy_MultipleValues(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                for ($i = 0; $i <= 3; $i++) {
                    $id = "people/" . $i;

                    $person = new Person();
                    $person->setName("Oren");
                    $person->setAge($i * 30);

                    $session->store($person, $id);

                    $tsf = $session->timeSeriesFor($id, "Heartrate");

                    $tsf->append(DateUtils::addMinutes($baseLine, 61), [ 59, 159 ], "watches/fitbit");
                    $tsf->append(DateUtils::addMinutes($baseLine, 62), [ 79, 179 ], "watches/fitbit");
                    $tsf->append(DateUtils::addMinutes($baseLine, 63), 69, "watches/apple");

                    $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 61), 1), [ 159, 259 ], "watches/fitbit");
                    $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 62), 1), [ 179 ], "watches/apple");
                    $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 63), 1), [ 169, 269 ], "watches/fitbit");

                    $session->saveChanges();
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->advanced()->rawQuery(
                        TimeSeriesRawResult::class, "declare timeseries out(x)\n" .
                        "{\n" .
                        "    from x.HeartRate between \$start and \$end\n" .
                        "}\n" .
                        "from People as doc\n" .
                        "where doc.age > 49\n" .
                        "select out(doc)")
                        ->addParameter("start", $baseLine)
                        ->addParameter("end", DateUtils::addMonths($baseLine, 2));

                $result = $query->toList();

                $this->assertCount(2, $result);

                for ($i = 0; $i < 2; $i++) {
                    $agg = $result[$i];

                    $this->assertCount(6, $agg->getResults());

                    $val = $agg->getResults()[0];

                    $this->assertCount(2,$val->getValues());
                    $this->assertEquals(59, $val->getValues()[0]);
                    $this->assertEquals(159, $val->getValues()[1]);

                    $this->assertEquals("watches/fitbit", $val->getTag());
                    $this->assertEquals(DateUtils::addMinutes($baseLine, 61), $val->getTimestamp());

                    $val = $agg->getResults()[1];

                    $this->assertCount(2, $val->getValues());
                    $this->assertEquals(79, $val->getValues()[0]);
                    $this->assertEquals(179, $val->getValues()[1]);

                    $this->assertEquals("watches/fitbit", $val->getTag());
                    $this->assertEquals(DateUtils::addMinutes($baseLine, 62), $val->getTimestamp());

                    $val = $agg->getResults()[2];

                    $this->assertCount(1, $val->getValues());
                    $this->assertEquals(69, $val->getValues()[0]);

                    $this->assertEquals("watches/apple", $val->getTag());
                    $this->assertEquals(DateUtils::addMinutes($baseLine, 63), $val->getTimestamp());

                    $val = $agg->getResults()[3];

                    $this->assertCount(2, $val->getValues());
                    $this->assertEquals(159, $val->getValues()[0]);
                    $this->assertEquals(259, $val->getValues()[1]);

                    $this->assertEquals("watches/fitbit", $val->getTag());
                    $this->assertEquals(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 61), 1), $val->getTimestamp());

                    $val = $agg->getResults()[4];

                    $this->assertCount(1, $val->getValues());
                    $this->assertEquals(179, $val->getValues()[0]);

                    $this->assertEquals("watches/apple", $val->getTag());
                    $this->assertEquals(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 62), 1), $val->getTimestamp());

                    $val = $agg->getResults()[5];

                    $this->assertCount(2, $val->getValues());

                    $this->assertEquals(169, $val->getValues()[0]);
                    $this->assertEquals(269, $val->getValues()[1]);

                    $this->assertEquals("watches/fitbit",$val->getTag());
                    $this->assertEquals(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 63), 1), $val->getTimestamp());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
