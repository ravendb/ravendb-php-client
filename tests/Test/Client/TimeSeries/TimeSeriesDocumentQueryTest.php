<?php

namespace tests\RavenDB\Test\Client\TimeSeries;

use DateTime;
use RavenDB\Documents\Queries\TimeSeries\TimeSeriesAggregationResult;
use RavenDB\Documents\Queries\TimeSeries\TimeSeriesRangeAggregationArray;
use RavenDB\Documents\Queries\TimeSeries\TimeSeriesRawResult;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class TimeSeriesDocumentQueryTest extends RemoteTestBase
{
    public function testCanQueryTimeSeriesUsingDocumentQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $user->setAge(35);

                $session->store($user, "users/ayende");

                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                $tsf->append(DateUtils::addMinutes($baseLine, 61), 59, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 62), 79, "watches/apple");
                $tsf->append(DateUtils::addMinutes($baseLine, 63), 69, "watches/fitbit");

                $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 61), 1), [ 159 ], "watches/apple");
                $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 62), 1), [ 179 ], "watches/apple");
                $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 63), 1), [ 169 ], "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsQueryText = "from Heartrate between \$start and \$end\n" .
                        "where Tag = 'watches/fitbit'\n" .
                        "group by '1 month'\n" .
                        "select min(), max(), avg()";

                $query = $session->advanced()->documentQuery(User::class)
                        ->whereGreaterThan("age", 21)
                        ->selectTimeSeries(TimeSeriesAggregationResult::class, function($b) use ($tsQueryText) { $b->raw($tsQueryText); })
                        ->addParameter("start", $baseLine)
                        ->addParameter("end", DateUtils::addMonths($baseLine, 3));

                $result = $query->toList();

                $this->assertCount(1, $result);
                $this->assertEquals(3, $result[0]->getCount());

                /** @var TimeSeriesRangeAggregationArray $agg */
                $agg = $result[0]->getResults();
                $this->assertCount(2, $agg);

                $this->assertEquals(69, $agg[0]->getMax()[0]);
                $this->assertEquals(59, $agg[0]->getMin()[0]);
                $this->assertEquals(64, $agg[0]->getAverage()[0]);

                $this->assertEquals(169, $agg[1]->getMax()[0]);
                $this->assertEquals(169, $agg[1]->getMin()[0]);
                $this->assertEquals(169, $agg[1]->getAverage()[0]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanQueryTimeSeriesRawValuesUsingDocumentQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $user->setAge(35);

                $session->store($user, "users/ayende");

                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                $tsf->append(DateUtils::addMinutes($baseLine, 61), 59, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 62), 79, "watches/apple");
                $tsf->append(DateUtils::addMinutes($baseLine, 63), 69, "watches/fitbit");

                $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 61), 1), [ 159 ], "watches/apple");
                $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 62), 1), [ 179 ], "watches/apple");
                $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 63), 1), [ 169 ], "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsQueryText = "from Heartrate between \$start and \$end\n" .
                        "where Tag = 'watches/fitbit'";

                $query = $session->advanced()->documentQuery(User::class)
                        ->whereGreaterThan("age", 21)
                        ->selectTimeSeries(TimeSeriesRawResult::class, function($b) use ($tsQueryText) { $b->raw($tsQueryText); })
                        ->addParameter("start", $baseLine)
                        ->addParameter("end", DateUtils::addMonths($baseLine, 3));

                $result = $query->toList();

                $this->assertCount(1, $result);
                $this->assertEquals(3, $result[0]->getCount());


                $values = $result[0]->getResults();

                $this->assertCount(3, $values);

                $this->assertEquals([ 59 ], $values[0]->getValues());
                $this->assertEquals("watches/fitbit", $values[0]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 61), $values[0]->getTimestamp());

                $this->assertEquals([ 69 ], $values[1]->getValues());
                $this->assertEquals("watches/fitbit", $values[1]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 63), $values[1]->getTimestamp());

                $this->assertEquals([ 169 ], $values[2]->getValues());
                $this->assertEquals("watches/fitbit", $values[2]->getTag());
                $this->assertEquals(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 63), 1),$values[2]->getTimestamp());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
