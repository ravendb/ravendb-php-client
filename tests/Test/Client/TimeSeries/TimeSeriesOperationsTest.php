<?php

namespace tests\RavenDB\Test\Client\TimeSeries;

use DateTime;
use Exception;
use RavenDB\Documents\Operations\TimeSeries\AppendOperation;
use RavenDB\Documents\Operations\TimeSeries\DeleteOperation;
use RavenDB\Documents\Operations\TimeSeries\GetMultipleTimeSeriesOperation;
use RavenDB\Documents\Operations\TimeSeries\GetTimeSeriesOperation;
use RavenDB\Documents\Operations\TimeSeries\GetTimeSeriesStatisticsOperation;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesBatchOperation;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesItemDetail;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesOperation;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRange;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeResult;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesStatistics;
use RavenDB\Documents\Queries\TimeSeries\TimeSeriesRangeAggregation;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntry;
use RavenDB\Exceptions\Documents\DocumentDoesNotExistException;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class TimeSeriesOperationsTest extends RemoteTestBase
{
    public function testCanCreateAndGetSimpleTimeSeriesUsingStoreOperations(): void
    {
        $documentId = "users/ayende";

        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $append1 = new AppendOperation();
            $append1->setTag("watches/fitbit");
            $append1->setTimestamp(DateUtils::addSeconds($baseLine, 1));
            $append1->setValues([ 59 ]);

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");
            $timeSeriesOp->append($append1);

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            $timeSeriesRangeResult = $store->operations()
                    ->send(new GetTimeSeriesOperation($documentId, "Heartrate"));

            $this->assertCount(1, $timeSeriesRangeResult->getEntries());

            /** @var TimeSeriesEntry  $value */
            $value = $timeSeriesRangeResult->getEntries()[0];
            $this->assertEqualsWithDelta(59, $value->getValues()[0], 0.001);

            $this->assertEquals("watches/fitbit", $value->getTag());
            $this->assertEquals(DateUtils::addSeconds($baseLine, 1), $value->getTimestamp());
        } finally {
            $store->close();
        }
    }

    public function testCanGetNonExistedRange(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, "users/ayende");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $timeSeriesOp = new TimeSeriesOperation();
            $timeSeriesOp->setName("Heartrate");
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 1), [ 59 ], "watches/fitbit"));

            $timeSeriesBatch = new TimeSeriesBatchOperation("users/ayende", $timeSeriesOp);
            $store->operations()->send($timeSeriesBatch);

            $timeSeriesRangeResult = $store->operations()->send(
                    new GetTimeSeriesOperation("users/ayende", "Heartrate",
                            DateUtils::addMonths($baseLine, -2), DateUtils::addMonths($baseLine, -1)));

            $this->assertEmpty($timeSeriesRangeResult->getEntries());
        } finally {
            $store->close();
        }
    }

    public function testCanStoreAndReadMultipleTimestampsUsingStoreOperations(): void
    {
        $documentId = "users/ayende";

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");
            $timeSeriesOp->append(
                    new AppendOperation(
                            DateUtils::addSeconds($baseLine, 1), [ 59 ], "watches/fitbit"));
            $timeSeriesOp->append(
                    new AppendOperation(
                            DateUtils::addSeconds($baseLine, 2), [ 61 ], "watches/fitbit"));
            $timeSeriesOp->append(
                    new AppendOperation(
                            DateUtils::addSeconds($baseLine, 5), [ 60 ], "watches/apple-watch"));

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            $timeSeriesRangeResult = $store->operations()
                    ->send(new GetTimeSeriesOperation($documentId, "Heartrate"));

            $this->assertCount(3, $timeSeriesRangeResult->getEntries());

            $value = $timeSeriesRangeResult->getEntries()[0];

            $this->assertEqualsWithDelta(59, $value->getValues()[0], 0.01);
            $this->assertEquals("watches/fitbit", $value->getTag());
            $this->assertEquals(DateUtils::addSeconds($baseLine, 1), $value->getTimestamp());

            $value = $timeSeriesRangeResult->getEntries()[1];

            $this->assertEqualsWithDelta(61, $value->getValues()[0], 0.01);
            $this->assertEquals("watches/fitbit", $value->getTag());
            $this->assertEquals(DateUtils::addSeconds($baseLine, 2), $value->getTimestamp());

            $value = $timeSeriesRangeResult->getEntries()[2];

            $this->assertEqualsWithDelta(60, $value->getValues()[0], 0.01);
            $this->assertEquals("watches/apple-watch", $value->getTag());
            $this->assertEquals(DateUtils::addSeconds($baseLine, 5), $value->getTimestamp());
        } finally {
            $store->close();
        }
    }

    public function testCanDeleteTimestampUsingStoreOperations(): void
    {
        $documentId = "users/ayende";

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");

            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 1), [ 59 ], "watches/fitbit"));
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 2), [ 61 ], "watches/fitbit"));
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 3), [ 60 ], "watches/fitbit"));
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 4), [ 62.5 ], "watches/fitbit"));
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 5), [ 62 ], "watches/fitbit"));

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            /** @var TimeSeriesRangeResult $timeSeriesRangeResult */
            $timeSeriesRangeResult = $store->operations()->send(new GetTimeSeriesOperation($documentId, "Heartrate", null, null));

            $this->assertCount(5, $timeSeriesRangeResult->getEntries());

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");
            $timeSeriesOp->delete(new DeleteOperation(DateUtils::addSeconds($baseLine, 2), DateUtils::addSeconds($baseLine, 3)));

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            $timeSeriesRangeResult = $store->operations()->send(new GetTimeSeriesOperation($documentId, "Heartrate", null, null));

            $this->assertCount(3, $timeSeriesRangeResult->getEntries());

            /** @var TimeSeriesEntry $value */
            $value = $timeSeriesRangeResult->getEntries()[0];
            $this->assertEquals(59, $value->getValues()[0]);
            $this->assertEquals(DateUtils::addSeconds($baseLine, 1), $value->getTimestamp());

            $value = $timeSeriesRangeResult->getEntries()[1];
            $this->assertEquals(62.5, $value->getValues()[0]);
            $this->assertEquals(DateUtils::addSeconds($baseLine, 4), $value->getTimestamp());

            $value = $timeSeriesRangeResult->getEntries()[2];
            $this->assertEquals(62, $value->getValues()[0]);
            $this->assertEquals(DateUtils::addSeconds($baseLine, 5), $value->getTimestamp());

            $session = $store->openSession();
            try {
                $session->delete($documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, $documentId);
                $session->saveChanges();

                $tsf = $session->timeSeriesFor($documentId, "Heartrate");
                $tsf->append(DateUtils::addMinutes($baseLine, 1), [ 59 ], "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 2), [ 69 ], "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 3), [ 79 ], "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, $documentId);
                $session->timeSeriesFor($documentId, "Heartrate")
                        ->deleteAt(DateUtils::addMinutes($baseLine, 2));

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var array<TimeSeriesEntry> $vals */
                $vals = $session->timeSeriesFor($documentId, "Heartrate")
                        ->get(null, null);

                $this->assertCount(2, $vals);

                $this->assertEquals([ 59 ], $vals[0]->getValues());
                $this->assertEquals("watches/fitbit", $vals[0]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $vals[0]->getTimestamp());

                $this->assertEquals([ 79 ], $vals[1]->getValues());
                $this->assertEquals("watches/fitbit", $vals[1]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $vals[1]->getTimestamp());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanDeleteLargeRange(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::addSeconds(DateUtils::truncateDayOfMonth(new DateTime()), -1);

            $session = $store->openSession();
            try {
                $session->store(new User(), "foo/bar");
                $tsf = $session->timeSeriesFor("foo/bar", "BloodPressure");

                for ($j = 1; $j < 10_000; $j++) {
                    $offset = $j * 10;
                    $time = DateUtils::addSeconds($baseLine, $offset);

                    $tsf->append($time, [ $j ], "watches/apple");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $rawQuery = "declare timeseries blood_pressure(doc)\n" .
                    "  {\n" .
                    "      from doc.BloodPressure between \$start and \$end\n" .
                    "      group by 1h\n" .
                    "      select min(), max(), avg(), first(), last()\n" .
                    "  }\n" .
                    "  from Users as p\n" .
                    "  select blood_pressure(p) as bloodPressure";

            $session = $store->openSession();
            try {
                $query = $session
                        ->advanced()
                        ->rawQuery(_TimeSeriesRawQueryTest\RawQueryResult::class, $rawQuery)
                        ->addParameter("start", $baseLine)
                        ->addParameter("end", DateUtils::addDays($baseLine, 1));

                $result = $query->toList();

                $this->assertCount(1, $result);

                $agg = $result[0];

                $bloodPressure = $agg->getBloodPressure();
                $count = array_sum(
                    array_map(function ($x) { return $x->getCount()[0];}, $bloodPressure->getResults()->getArrayCopy()),
                );
                $this->assertEquals(8640, $count);
                $this->assertEquals($bloodPressure->getCount(), $count);
                $this->assertEquals(24, count($bloodPressure->getResults()));

                for ($index = 0; $index < count($bloodPressure->getResults()); $index++) {
                    /** @var TimeSeriesRangeAggregation $item */
                    $item = $bloodPressure->getResults()[$index];

                    $this->assertEquals(360, $item->getCount()[0]);
                    $this->assertEquals($index * 360 + 180 + 0.5, $item->getAverage()[0]);
                    $this->assertEquals(($index + 1) * 360, $item->getMax()[0]);
                    $this->assertEquals($index * 360 + 1, $item->getMin()[0]);
                    $this->assertEquals($index * 360 + 1, $item->getFirst()[0]);
                    $this->assertEquals(($index + 1) * 360, $item->getLast()[0]);
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor("foo/bar", "BloodPressure");
                $tsf->delete(DateUtils::addSeconds($baseLine, 3600), DateUtils::addSeconds($baseLine, 3600 * 10)); // remove 9 hours
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $sessionOptions = new SessionOptions();
            $sessionOptions->setNoCaching(true);

            $session = $store->openSession();
            try {
                $query = $session->advanced()->rawQuery(_TimeSeriesRawQueryTest\RawQueryResult::class, $rawQuery)
                        ->addParameter("start", $baseLine)
                        ->addParameter("end", DateUtils::addDays($baseLine, 1));

                $result = $query->toList();

                $agg = $result[0];

                $bloodPressure = $agg->getBloodPressure();
                $count = array_sum(
                    array_map(function ($x) { return $x->getCount()[0];}, $bloodPressure->getResults()->getArrayCopy()),
                );
                $this->assertEquals(5399, $count);
                $this->assertEquals($bloodPressure->getCount(), $count);
                $this->assertEquals(15, count($bloodPressure->getResults()));

                $index = 0;
                $item = $bloodPressure->getResults()[$index];
                $this->assertEquals(359, $item->getCount()[0]);
                $this->assertEquals(180, $item->getAverage()[0]);
                $this->assertEquals(359, $item->getMax()[0]);
                $this->assertEquals(1, $item->getMin()[0]);
                $this->assertEquals(1, $item->getFirst()[0]);
                $this->assertEquals(359, $item->getLast()[0]);

                for ($index = 1; $index < count($bloodPressure->getResults()); $index++) {
                    $item = $bloodPressure->getResults()[$index];
                    $realIndex = $index + 9;

                    $this->assertEquals(360, $item->getCount()[0]);
                    $this->assertEquals($realIndex * 360 + 180 + 0.5, $item->getAverage()[0]);
                    $this->assertEquals(($realIndex + 1) * 360, $item->getMax()[0]);
                    $this->assertEquals($realIndex * 360 + 1, $item->getMin()[0]);
                    $this->assertEquals($realIndex * 360 + 1, $item->getFirst()[0]);
                    $this->assertEquals(($realIndex + 1) * 360, $item->getLast()[0]);
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanAppendAndRemoveTimestampsInSingleBatch(): void
    {
        $documentId = "users/ayende";

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 1), [ 59 ], "watches/fitbit"));
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 2), [ 61 ], "watches/fitbit"));
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 3), [ 61.5 ], "watches/fitbit"));

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            $timeSeriesRangeResult = $store->operations()->send(new GetTimeSeriesOperation($documentId, "Heartrate", null, null));

            $this->assertCount(3, $timeSeriesRangeResult->getEntries());

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 4), [ 60 ], "watches/fitbit"));
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 5), [ 62.5 ], "watches/fitbit"));
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 6), [ 62 ], "watches/fitbit"));

            $timeSeriesOp->delete(new DeleteOperation(DateUtils::addSeconds($baseLine, 2), DateUtils::addSeconds($baseLine, 3)));

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            $timeSeriesRangeResult = $store->operations()->send(new GetTimeSeriesOperation($documentId, "Heartrate", null, null));

            $this->assertCount(4, $timeSeriesRangeResult->getEntries());

            $value = $timeSeriesRangeResult->getEntries()[0];
            $this->assertEquals(59, $value->getValues()[0]);
            $this->assertEquals(DateUtils::addSeconds($baseLine, 1), $value->getTimestamp());

            $value = $timeSeriesRangeResult->getEntries()[1];
            $this->assertEquals(60, $value->getValues()[0]);
            $this->assertEquals(DateUtils::addSeconds($baseLine, 4), $value->getTimestamp());

            $value = $timeSeriesRangeResult->getEntries()[2];
            $this->assertEquals(62.5, $value->getValues()[0]);
            $this->assertEquals(DateUtils::addSeconds($baseLine, 5), $value->getTimestamp());

            $value = $timeSeriesRangeResult->getEntries()[3];
            $this->assertEquals(62, $value->getValues()[0]);
            $this->assertEquals(DateUtils::addSeconds($baseLine, 6), $value->getTimestamp());
        } finally {
            $store->close();
        }
    }

    public function testShouldThrowOnAttemptToCreateTimeSeriesOnMissingDocument(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");
            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 1), [ 59 ], "watches/fitbit"));

            $timeSeriesBatch = new TimeSeriesBatchOperation("users/ayende", $timeSeriesOp);
            try {
                $store->operations()->send($timeSeriesBatch);

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(DocumentDoesNotExistException::class, $exception);
                $this->assertStringContainsString("Cannot operate on time series of a missing document", $exception->getMessage());
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetMultipleRangesInSingleRequest(): void
    {
        $documentId = "users/ayende";

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");

            for ($i = 0; $i <= 360; $i++) {
                $timeSeriesOp->append(
                        new AppendOperation(DateUtils::addSeconds($baseLine, $i * 10), [ 59 ], "watches/fitbit")
                );
            }

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);
            $store->operations()->send($timeSeriesBatch);

            $timeSeriesDetails = $store->operations()->send(new GetMultipleTimeSeriesOperation($documentId,
                    [
                            new TimeSeriesRange("Heartrate", DateUtils::addMinutes($baseLine, 5), DateUtils::addMinutes($baseLine, 10)),
                            new TimeSeriesRange("Heartrate", DateUtils::addMinutes($baseLine, 15), DateUtils::addMinutes($baseLine, 30)),
                            new TimeSeriesRange("Heartrate", DateUtils::addMinutes($baseLine, 40), DateUtils::addMinutes($baseLine, 60))
                    ]));

            $this->assertEquals($documentId, $timeSeriesDetails->getId());
            $this->assertCount(1, $timeSeriesDetails->getValues());
            $this->assertCount(3, $timeSeriesDetails->getValues()["Heartrate"]);

            /** @var TimeSeriesRangeResult $range */
            $range = $timeSeriesDetails->getValues()["Heartrate"][0];

            $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $range->getFrom());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $range->getTo());

            $this->assertCount(31,  $range->getEntries());

            $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $range->getEntries()[0]->getTimestamp());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $range->getEntries()[30]->getTimestamp());

            $range = $timeSeriesDetails->getValues()["Heartrate"][1];

            $this->assertEquals(DateUtils::addMinutes($baseLine, 15), $range->getFrom());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 30), $range->getTo());

            $this->assertCount(91, $range->getEntries());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 15), $range->getEntries()[0]->getTimestamp());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 30), $range->getEntries()[90]->getTimestamp());

            $range = $timeSeriesDetails->getValues()["Heartrate"][2];

            $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $range->getFrom());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 60), $range->getTo());

            $this->assertCount(121, $range->getEntries());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $range->getEntries()[0]->getTimestamp());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 60), $range->getEntries()[120]->getTimestamp());
        } finally {
            $store->close();
        }
    }

    public function testCanGetMultipleTimeSeriesInSingleRequest(): void
    {
        $store = $this->getDocumentStore();
        try {
            $documentId = "users/ayende";

            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            // append

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");

            for ($i = 0; $i <= 10; $i++) {
                $timeSeriesOp->append(
                        new AppendOperation(
                                DateUtils::addMinutes($baseLine, $i * 10), [ 72 ], "watches/fitbit"));
            }

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            $timeSeriesOp = new TimeSeriesOperation("BloodPressure");

            for ($i = 0; $i <= 10; $i++) {
                $timeSeriesOp->append(
                        new AppendOperation(
                                DateUtils::addMinutes($baseLine, $i * 10),
                                [ 80 ]
                        )
                );
            }

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            $timeSeriesOp = new TimeSeriesOperation("Temperature");

            for ($i = 0; $i <= 10; $i++) {
                $timeSeriesOp->append(
                        new AppendOperation(
                                DateUtils::addMinutes($baseLine, $i * 10),
                                [ 37 + $i * 0.15 ]
                        )
                );
            }

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            // get ranges from multiple time series in a single request

            $timeSeriesDetails = $store->operations()->send(new GetMultipleTimeSeriesOperation($documentId,
                    [
                        new TimeSeriesRange("Heartrate", $baseLine, DateUtils::addMinutes($baseLine, 15)),
                        new TimeSeriesRange("Heartrate", DateUtils::addMinutes($baseLine, 30), DateUtils::addMinutes($baseLine, 45)),
                        new TimeSeriesRange("BloodPressure", $baseLine, DateUtils::addMinutes($baseLine, 30)),
                        new TimeSeriesRange("BloodPressure", DateUtils::addMinutes($baseLine, 60), DateUtils::addMinutes($baseLine, 90)),
                        new TimeSeriesRange("Temperature", $baseLine, DateUtils::addDays($baseLine, 1))
                    ]));

            $this->assertEquals("users/ayende", $timeSeriesDetails->getId());
            $this->assertCount(3, $timeSeriesDetails->getValues());
            $this->assertCount(2, $timeSeriesDetails->getValues()["Heartrate"]);

            $range = $timeSeriesDetails->getValues()["Heartrate"][0];
            $this->assertEquals($baseLine, $range->getFrom());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 15), $range->getTo());

            $this->assertCount(2, $range->getEntries());

            $this->assertEquals($baseLine, $range->getEntries()[0]->getTimestamp());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $range->getEntries()[1]->getTimestamp());

            $this->assertNull($range->getTotalResults());

            $range = $timeSeriesDetails->getValues()["Heartrate"][1];

            $this->assertEquals(DateUtils::addMinutes($baseLine, 30), $range->getFrom());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 45), $range->getTo());

            $this->assertCount(2, $range->getEntries());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 30), $range->getEntries()[0]->getTimestamp());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $range->getEntries()[1]->getTimestamp());

            $this->assertNull($range->getTotalResults());

            $this->assertCount(2, $timeSeriesDetails->getValues()["BloodPressure"]);

            $range = $timeSeriesDetails->getValues()["BloodPressure"][0];

            $this->assertEquals($baseLine, $range->getFrom());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 30), $range->getTo());
            $this->assertCount(4, $range->getEntries());

            $this->assertEquals($baseLine, $range->getEntries()[0]->getTimestamp());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 30), $range->getEntries()[3]->getTimestamp());

            $this->assertNull($range->getTotalResults());

            $range = $timeSeriesDetails->getValues()["BloodPressure"][1];

            $this->assertEquals(DateUtils::addMinutes($baseLine, 60), $range->getFrom());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 90), $range->getTo());

            $this->assertCount(4, $range->getEntries());

            $this->assertEquals(DateUtils::addMinutes($baseLine, 60), $range->getEntries()[0]->getTimestamp());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 90), $range->getEntries()[3]->getTimestamp());

            $this->assertNull($range->getTotalResults());

            $this->assertCount(1, $timeSeriesDetails->getValues()["Temperature"]);

            $range = $timeSeriesDetails->getValues()["Temperature"][0];

            $this->assertEquals($baseLine, $range->getFrom());
            $this->assertEquals(DateUtils::addDays($baseLine, 1), $range->getTo());

            $this->assertCount(11, $range->getEntries());

            $this->assertEquals($baseLine, $range->getEntries()[0]->getTimestamp());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 100), $range->getEntries()[10]->getTimestamp());

            $this->assertEquals(11, $range->getTotalResults());
        } finally {
            $store->close();
        }
    }

    public function testShouldThrowOnNullOrEmptyRanges(): void
    {
        $store = $this->getDocumentStore();
        try {
            $documentId = "users/ayende";

            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");

            for ($i = 0; $i <= 10; $i++) {
                $timeSeriesOp->append(
                        new AppendOperation(
                                DateUtils::addMinutes($baseLine, $i * 10), [ 72 ], "watches/fitbit"));
            }

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            try {
                $store->operations()->send(new GetTimeSeriesOperation("users/ayende", null));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(IllegalArgumentException::class, $exception);
            }

            try {
                $store->operations()->send(new GetMultipleTimeSeriesOperation("users/ayende", []));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(IllegalArgumentException::class, $exception);
            }

        } finally {
            $store->close();
        }
    }

    public function testGetMultipleTimeSeriesShouldThrowOnMissingNameFromRange(): void
    {
        $store = $this->getDocumentStore();
        try {
            $documentId = "users/ayende";

            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");

            for ($i = 0; $i <= 10; $i++) {
                $timeSeriesOp->append(
                        new AppendOperation(
                                DateUtils::addMinutes($baseLine, $i * 10), [ 72 ], "watches/fitbit"));
            }

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            try {
                $store->operations()->send(new GetMultipleTimeSeriesOperation("users/ayende",
                        [
                            new TimeSeriesRange(null, $baseLine, null)
                        ]));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(IllegalArgumentException::class, $exception);
                $this->assertStringContainsString("Name cannot be null or empty", $exception->getMessage());
            }
        } finally {
            $store->close();
        }
    }

    public function testGetTimeSeriesShouldThrowOnMissingName(): void
    {
        $store = $this->getDocumentStore();
        try {
            $documentId = "users/ayende";

            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $timeSeriesOp = new TimeSeriesOperation("Heartrate");

            for ($i = 0; $i <= 10; $i++) {
                $timeSeriesOp->append(
                        new AppendOperation(
                                DateUtils::addMinutes($baseLine, $i * 10), [ 72 ], "watches/fitbit"));
            }

            $timeSeriesBatch = new TimeSeriesBatchOperation($documentId, $timeSeriesOp);

            $store->operations()->send($timeSeriesBatch);

            try {
                $store->operations()->send(new GetTimeSeriesOperation("users/ayende", "", $baseLine, DateUtils::addYears($baseLine, 10)));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(IllegalArgumentException::class, $exception);
                $this->assertStringContainsString("Timeseries cannot be null or empty", $exception->getMessage());
            }
        } finally {
            $store->close();
        }
    }

    public function testGetTimeSeriesStatistics(): void
    {
        $store = $this->getDocumentStore();
        try {
            $documentId = "users/ayende";
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, $documentId);

                $ts = $session->timeSeriesFor($documentId, "heartrate");
                for ($i = 0; $i <= 10; $i++) {
                    $ts->append(DateUtils::addMinutes($baseLine, $i * 10), 72, "watches/fitbit");
                }

                $ts = $session->timeSeriesFor($documentId, "pressure");
                for ($i = 10; $i <= 20; $i++) {
                    $ts->append(DateUtils::addMinutes($baseLine, $i * 10), 72, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var TimeSeriesStatistics $op */
            $op = $store->operations()->send(new GetTimeSeriesStatisticsOperation($documentId));

            $this->assertEquals($documentId, $op->getDocumentId());
            $this->assertCount(2, $op->getTimeSeries());

            /** @var TimeSeriesItemDetail $ts1 */
            $ts1 = $op->getTimeSeries()[0];
            /** @var TimeSeriesItemDetail $ts2 */
            $ts2 = $op->getTimeSeries()[1];

            $this->assertEquals("heartrate", $ts1->getName());
            $this->assertEquals("pressure", $ts2->getName());

            $this->assertEquals(11, $ts1->getNumberOfEntries());
            $this->assertEquals(11, $ts2->getNumberOfEntries());

            $this->assertEquals($baseLine, $ts1->getStartDate());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 10 * 10), $ts2->getStartDate());

            $this->assertEquals(DateUtils::addMinutes($baseLine, 10 * 10), $ts1->getEndDate());
            $this->assertEquals(DateUtils::addMinutes($baseLine, 20 * 10), $ts2->getEndDate());
        } finally {
            $store->close();
        }
    }

    public function testCanDeleteWithoutProvidingFromAndToDates(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $docId = "users/ayende";

            $session = $store->openSession();
            try {
                $session->store(new User(), $docId);

                $tsf = $session->timeSeriesFor($docId, "HeartRate");
                $tsf2 = $session->timeSeriesFor($docId, "BloodPressure");
                $tsf3 = $session->timeSeriesFor($docId, "BodyTemperature");

                for ($j = 0; $j < 100; $j++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $j), $j);
                    $tsf2->append(DateUtils::addMinutes($baseLine, $j), $j);
                    $tsf3->append(DateUtils::addMinutes($baseLine, $j), $j);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var TimeSeriesRangeResult $get */
            $get = $store->operations()->send(new GetTimeSeriesOperation($docId, "HeartRate"));
            $this->assertCount(100, $get->getEntries());

            // null From, To
            $deleteOp = new TimeSeriesOperation();
            $deleteOp->setName("Heartrate");
            $deleteOp->delete(new DeleteOperation());

            $store->operations()->send(new TimeSeriesBatchOperation($docId, $deleteOp));

            $get = $store->operations()->send(new GetTimeSeriesOperation($docId, "HeartRate"));

            $this->assertNull($get);

            $get = $store->operations()->send(new GetTimeSeriesOperation($docId, "BloodPressure"));
            $this->assertCount(100, $get->getEntries());

            // null to
            $deleteOp = new TimeSeriesOperation();
            $deleteOp->setName("BloodPressure");
            $deleteOp->delete(new DeleteOperation(DateUtils::addMinutes($baseLine, 50), null));

            $store->operations()->send(new TimeSeriesBatchOperation($docId, $deleteOp));

            $get = $store->operations()->send(new GetTimeSeriesOperation($docId, "BloodPressure"));
            $this->assertCount(50, $get->getEntries());

            $get = $store->operations()->send(new GetTimeSeriesOperation($docId, "BodyTemperature"));
            $this->assertCount(100, $get->getEntries());

            // null From
            $deleteOp = new TimeSeriesOperation();
            $deleteOp->setName("BodyTemperature");
            $deleteOp->delete(new DeleteOperation(null, DateUtils::addMinutes($baseLine, 19)));

            $store->operations()->send(new TimeSeriesBatchOperation($docId, $deleteOp));

            $get = $store->operations()->send(new GetTimeSeriesOperation($docId, "BodyTemperature"));
            $this->assertCount(80, $get->getEntries());
        } finally {
            $store->close();
        }
    }
}
