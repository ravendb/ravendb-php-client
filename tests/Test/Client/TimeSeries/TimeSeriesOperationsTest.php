<?php

namespace tests\RavenDB\Test\Client\TimeSeries;

use DateTime;
use RavenDB\Documents\Operations\TimeSeries\AppendOperation;
use RavenDB\Documents\Operations\TimeSeries\DeleteOperation;
use RavenDB\Documents\Operations\TimeSeries\GetTimeSeriesOperation;
use RavenDB\Documents\Operations\TimeSeries\GetTimeSeriesStatisticsOperation;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesBatchOperation;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesItemDetail;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesOperation;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeResult;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesStatistics;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntry;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class TimeSeriesOperationsTest extends RemoteTestBase
{
//    public function canCreateAndGetSimpleTimeSeriesUsingStoreOperations(): void {
//        String documentId = "users/ayende";
//
//        try (IDocumentStore store = getDocumentStore()) {
//
//            try (IDocumentSession session = $store->openSession()) {
//                User user = new User();
//                $session->store(user, documentId);
//                $session->saveChanges();
//            }
//
//            Date baseLine = DateUtils.truncate(new Date(), Calendar.DAY_OF_MONTH);
//
//            AppendOperation append1 = new AppendOperation();
//            append1.setTag("watches/fitbit");
//            append1.setTimestamp(DateUtils::addSeconds($baseLine, 1));
//            append1.setValues(new double[] { 59 ]);
//
//            TimeSeriesOperation timeSeriesOp = new TimeSeriesOperation("Heartrate");
//            $timeSeriesOp->append(append1);
//
//            TimeSeriesBatchOperation timeSeriesBatch = new TimeSeriesBatchOperation(documentId, timeSeriesOp);
//
//            $store->operations()->send(timeSeriesBatch);
//
//            TimeSeriesRangeResult timeSeriesRangeResult = $store->operations()
//                    ->send(new GetTimeSeriesOperation(documentId, "Heartrate"));
//
//            assertThat(timeSeriesRangeResult.getEntries())
//                    .hasSize(1);
//
//            TimeSeriesEntry value = timeSeriesRangeResult.getEntries()[0];
//            assertThat(value.getValues()[0])
//                    .isEqualTo(59, Offset.offset(0.001));
//            assertThat(value.getTag())
//                    .isEqualTo("watches/fitbit");
//            assertThat(value.getTimestamp())
//                    .isEqualTo(DateUtils::addSeconds($baseLine, 1));
//        }
//    }
//
//    @Test
//    public function canGetNonExistedRange(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = $store->openSession()) {
//                User user = new User();
//                $session->store(user, "users/ayende");
//                $session->saveChanges();
//            }
//
//            Date baseLine = DateUtils.truncate(new Date(), Calendar.DAY_OF_MONTH);
//
//            TimeSeriesOperation timeSeriesOp = new TimeSeriesOperation();
//            timeSeriesOp.setName("Heartrate");
//            $timeSeriesOp->append(new AppendOperation(
//                    DateUtils::addSeconds($baseLine, 1), new double[]{59}, "watches/fitbit"));
//
//            TimeSeriesBatchOperation timeSeriesBatch = new TimeSeriesBatchOperation("users/ayende", timeSeriesOp);
//            $store->operations()->send(timeSeriesBatch);
//
//            TimeSeriesRangeResult timeSeriesRangeResult = $store->operations()->send(
//                    new GetTimeSeriesOperation("users/ayende", "Heartrate",
//                            DateUtils.addMonths(baseLine, -2), DateUtils.addMonths(baseLine, -1)));
//
//            assertThat(timeSeriesRangeResult.getEntries())
//                    .isEmpty();
//        }
//    }
//
//    @Test
//    public function canStoreAndReadMultipleTimestampsUsingStoreOperations(): void {
//        String documentId = "users/ayende";
//
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = $store->openSession()) {
//                $session->store(new User(), documentId);
//                $session->saveChanges();
//            }
//
//            Date baseLine = DateUtils.truncate(new Date(), Calendar.DAY_OF_MONTH);
//
//            TimeSeriesOperation timeSeriesOp = new TimeSeriesOperation("Heartrate");
//            $timeSeriesOp->append(
//                    new AppendOperation(
//                            DateUtils::addSeconds($baseLine, 1), new double[]{59}, "watches/fitbit"));
//            $timeSeriesOp->append(
//                    new AppendOperation(
//                            DateUtils::addSeconds($baseLine, 2), new double[]{61}, "watches/fitbit"));
//            $timeSeriesOp->append(
//                    new AppendOperation(
//                            DateUtils::addSeconds($baseLine, 5), new double[]{60}, "watches/apple-watch"));
//
//            TimeSeriesBatchOperation timeSeriesBatch = new TimeSeriesBatchOperation(documentId, timeSeriesOp);
//
//            $store->operations()->send(timeSeriesBatch);
//
//            TimeSeriesRangeResult timeSeriesRangeResult = $store->operations()
//                    ->send(new GetTimeSeriesOperation(documentId, "Heartrate"));
//
//            assertThat(timeSeriesRangeResult.getEntries())
//                    .hasSize(3);
//
//            TimeSeriesEntry value = timeSeriesRangeResult.getEntries()[0];
//
//            assertThat(value.getValues()[0])
//                    .isEqualTo(59, Offset.offset(0.01));
//            assertThat(value.getTag())
//                    .isEqualTo("watches/fitbit");
//            assertThat(value.getTimestamp())
//                    .isEqualTo(DateUtils::addSeconds($baseLine, 1));
//
//            value = timeSeriesRangeResult.getEntries()[1];
//
//            assertThat(value.getValues()[0])
//                    .isEqualTo(61, Offset.offset(0.01));
//            assertThat(value.getTag())
//                    .isEqualTo("watches/fitbit");
//            assertThat(value.getTimestamp())
//                    .isEqualTo(DateUtils::addSeconds($baseLine, 2));
//
//            value = timeSeriesRangeResult.getEntries()[2];
//
//            assertThat(value.getValues()[0])
//                    .isEqualTo(60, Offset.offset(0.01));
//            assertThat(value.getTag())
//                    .isEqualTo("watches/apple-watch");
//            assertThat(value.getTimestamp())
//                    .isEqualTo(DateUtils::addSeconds($baseLine, 5));
//        }
//    }

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
                        ->delete(DateUtils::addMinutes($baseLine, 2));

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

//    public function canDeleteLargeRange(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            Date baseLine = DateUtils.addSeconds(DateUtils.truncate(new Date(), Calendar.DAY_OF_MONTH), -1);
//
//            try (IDocumentSession session = $store->openSession()) {
//                $session->store(new User(), "foo/bar");
//                ISessionDocumentTimeSeries $tsf = $session->timeSeriesFor("foo/bar", "BloodPressure");
//
//                for (int j = 1; j < 10_000; j++) {
//                    int offset = j * 10;
//                    Date time = DateUtils::addSeconds($baseLine, offset);
//
//                    $tsf.append(time, new double[]{j}, "watches/apple");
//                }
//
//                $session->saveChanges();
//            }
//
//            String rawQuery = "declare timeseries blood_pressure(doc)\n" +
//                    "  {\n" +
//                    "      from doc.BloodPressure between $start and $end\n" +
//                    "      group by 1h\n" +
//                    "      select min(), max(), avg(), first(), last()\n" +
//                    "  }\n" +
//                    "  from Users as p\n" +
//                    "  select blood_pressure(p) as bloodPressure";
//
//            try (IDocumentSession session = $store->openSession()) {
//                IRawDocumentQuery<TimeSeriesRawQueryTest.RawQueryResult> query = session
//                        .advanced()
//                        .rawQuery(TimeSeriesRawQueryTest.RawQueryResult.class, rawQuery)
//                        .addParameter("start", baseLine)
//                        .addParameter("end", DateUtils.addDays(baseLine, 1));
//
//                List<TimeSeriesRawQueryTest.RawQueryResult> result = query.toList();
//
//                assertThat(result)
//                        .hasSize(1);
//
//                TimeSeriesRawQueryTest.RawQueryResult agg = result.get(0);
//
//                TimeSeriesAggregationResult bloodPressure = agg.getBloodPressure();
//                Long count = Arrays.stream(bloodPressure.getResults()).map(x -> x.getCount()[0])
//                        .reduce(0L, Long::sum);
//                assertThat(count)
//                        .isEqualTo(8640);
//                assertThat(count)
//                        .isEqualTo(bloodPressure.getCount());
//                assertThat(bloodPressure.getResults().length)
//                        .isEqualTo(24);
//
//                for (int index = 0; index < bloodPressure.getResults().length; index++) {
//                    TimeSeriesRangeAggregation item = bloodPressure.getResults()[index];
//
//                    assertThat(item.getCount()[0])
//                            .isEqualTo(360);
//                    assertThat(item.getAverage()[0])
//                            .isEqualTo(index * 360 + 180 + 0.5);
//                    assertThat(item.getMax()[0])
//                            .isEqualTo((index + 1) * 360);
//                    assertThat(item.getMin()[0])
//                            .isEqualTo(index * 360 + 1);
//                    assertThat(item.getFirst()[0])
//                            .isEqualTo(index * 360 + 1);
//                    assertThat(item.getLast()[0])
//                            .isEqualTo((index + 1) * 360);
//                }
//            }
//
//            try (IDocumentSession session = $store->openSession()) {
//                ISessionDocumentTimeSeries $tsf = $session->timeSeriesFor("foo/bar", "BloodPressure");
//                $tsf.delete(DateUtils::addSeconds($baseLine, 3600), DateUtils::addSeconds($baseLine, 3600 * 10)); // remove 9 hours
//                $session->saveChanges();
//            }
//
//            SessionOptions sessionOptions = new SessionOptions();
//            sessionOptions.setNoCaching(true);
//            try (IDocumentSession session = $store->openSession(sessionOptions)) {
//                IRawDocumentQuery<TimeSeriesRawQueryTest.RawQueryResult> query = $session->advanced().rawQuery(TimeSeriesRawQueryTest.RawQueryResult.class, rawQuery)
//                        .addParameter("start", baseLine)
//                        .addParameter("end", DateUtils.addDays(baseLine, 1));
//
//                List<TimeSeriesRawQueryTest.RawQueryResult> result = query.toList();
//
//                TimeSeriesRawQueryTest.RawQueryResult agg = result.get(0);
//
//                TimeSeriesAggregationResult bloodPressure = agg.getBloodPressure();
//                Long count = Arrays.stream(bloodPressure.getResults()).map(x -> x.getCount()[0])
//                        .reduce(0L, Long::sum);
//                assertThat(count)
//                        .isEqualTo(5399);
//                assertThat(count)
//                        .isEqualTo(bloodPressure.getCount());
//                assertThat(bloodPressure.getResults().length)
//                        .isEqualTo(15);
//
//                int index = 0;
//                TimeSeriesRangeAggregation item = bloodPressure.getResults()[index];
//                assertThat(item.getCount()[0])
//                        .isEqualTo(359);
//                assertThat(item.getAverage()[0])
//                        .isEqualTo(180);
//                assertThat(item.getMax()[0])
//                        .isEqualTo(359);
//                assertThat(item.getMin()[0])
//                        .isEqualTo(1);
//                assertThat(item.getFirst()[0])
//                        .isEqualTo(1);
//                assertThat(item.getLast()[0])
//                        .isEqualTo(359);
//
//                for (index = 1; index < bloodPressure.getResults().length; index++) {
//                    item = bloodPressure.getResults()[index];
//                    int realIndex = index + 9;
//
//                    assertThat(item.getCount()[0])
//                            .isEqualTo(360);
//                    assertThat(item.getAverage()[0])
//                            .isEqualTo(realIndex * 360 + 180 + 0.5);
//                    assertThat(item.getMax()[0])
//                            .isEqualTo((realIndex + 1) * 360);
//                    assertThat(item.getMin()[0])
//                            .isEqualTo(realIndex * 360 + 1);
//                    assertThat(item.getFirst()[0])
//                            .isEqualTo(realIndex * 360 + 1);
//                    assertThat(item.getLast()[0])
//                            .isEqualTo((realIndex + 1) * 360);
//                }
//            }
//        }
//    }
//
//    @Test
//    public function canAppendAndRemoveTimestampsInSingleBatch(): void {
//        String documentId = "users/ayende";
//
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = $store->openSession()) {
//                $session->store(new User(), documentId);
//                $session->saveChanges();
//            }
//
//            Date baseLine = DateUtils.truncate(new Date(), Calendar.DAY_OF_MONTH);
//
//            TimeSeriesOperation timeSeriesOp = new TimeSeriesOperation("Heartrate");
//            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 1), new double[]{59}, "watches/fitbit"));
//            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 2), new double[]{61}, "watches/fitbit"));
//            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 3), new double[]{61.5}, "watches/fitbit"));
//
//            TimeSeriesBatchOperation timeSeriesBatch = new TimeSeriesBatchOperation(documentId, timeSeriesOp);
//
//            $store->operations()->send(timeSeriesBatch);
//
//            TimeSeriesRangeResult timeSeriesRangeResult = $store->operations()->send(new GetTimeSeriesOperation(documentId, "Heartrate", null, null));
//
//            assertThat(timeSeriesRangeResult.getEntries())
//                    .hasSize(3);
//
//            timeSeriesOp = new TimeSeriesOperation("Heartrate");
//            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 4), [ 60 ], "watches/fitbit"));
//            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 5), [ 62.5 ], "watches/fitbit"));
//            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 6), [ 62 ], "watches/fitbit"));
//
//            timeSeriesOp.delete(new TimeSeriesOperation.DeleteOperation(DateUtils::addSeconds($baseLine, 2), DateUtils::addSeconds($baseLine, 3)));
//
//            timeSeriesBatch = new TimeSeriesBatchOperation(documentId, timeSeriesOp);
//
//            $store->operations()->send(timeSeriesBatch);
//
//            timeSeriesRangeResult = $store->operations()->send(new GetTimeSeriesOperation(documentId, "Heartrate", null, null));
//
//            assertThat(timeSeriesRangeResult.getEntries())
//                    .hasSize(4);
//
//            TimeSeriesEntry value = timeSeriesRangeResult.getEntries()[0];
//            assertThat(value.getValues()[0])
//                    .isEqualTo(59);
//            assertThat(value.getTimestamp())
//                    .isEqualTo(DateUtils::addSeconds($baseLine, 1));
//
//            value = timeSeriesRangeResult.getEntries()[1];
//            assertThat(value.getValues()[0])
//                    .isEqualTo(60);
//            assertThat(value.getTimestamp())
//                    .isEqualTo(DateUtils::addSeconds($baseLine, 4));
//
//            value = timeSeriesRangeResult.getEntries()[2];
//            assertThat(value.getValues()[0])
//                    .isEqualTo(62.5);
//            assertThat(value.getTimestamp())
//                    .isEqualTo(DateUtils::addSeconds($baseLine, 5));
//
//            value = timeSeriesRangeResult.getEntries()[3];
//            assertThat(value.getValues()[0])
//                    .isEqualTo(62);
//            assertThat(value.getTimestamp())
//                    .isEqualTo(DateUtils::addSeconds($baseLine, 6));
//        }
//    }
//
//    @Test
//    public function shouldThrowOnAttemptToCreateTimeSeriesOnMissingDocument(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            Date baseLine = DateUtils.truncate(new Date(), Calendar.DAY_OF_MONTH);
//
//            TimeSeriesOperation timeSeriesOp = new TimeSeriesOperation("Heartrate");
//            $timeSeriesOp->append(new AppendOperation(DateUtils::addSeconds($baseLine, 1), new double[]{59}, "watches/fitbit"));
//
//            TimeSeriesBatchOperation timeSeriesBatch = new TimeSeriesBatchOperation("users/ayende", timeSeriesOp);
//            assertThatThrownBy(() -> {
//                $store->operations()->send(timeSeriesBatch);
//            })
//                    .isInstanceOf(DocumentDoesNotExistException.class)
//                    .hasMessageContaining("Cannot operate on time series of a missing document");
//        }
//    }
//
//    @Test
//    public function canGetMultipleRangesInSingleRequest(): void {
//        String documentId = "users/ayende";
//
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = $store->openSession()) {
//                $session->store(new User(), documentId);
//                $session->saveChanges();
//            }
//
//            Date baseLine = DateUtils.truncate(new Date(), Calendar.DAY_OF_MONTH);
//
//            TimeSeriesOperation timeSeriesOp = new TimeSeriesOperation("Heartrate");
//
//            for (int i = 0; i <= 360; i++) {
//                $timeSeriesOp->append(
//                        new AppendOperation(DateUtils::addSeconds($baseLine, i * 10), [ 59 ], "watches/fitbit")
//                );
//            }
//
//            TimeSeriesBatchOperation timeSeriesBatch = new TimeSeriesBatchOperation(documentId, timeSeriesOp);
//            $store->operations()->send(timeSeriesBatch);
//
//            TimeSeriesDetails timeSeriesDetails = $store->operations()->send(new GetMultipleTimeSeriesOperation(documentId,
//                    Arrays.asList(
//                            new TimeSeriesRange("Heartrate", DateUtils::addMinutes($baseLine, 5), DateUtils::addMinutes($baseLine, 10)),
//                            new TimeSeriesRange("Heartrate", DateUtils::addMinutes($baseLine, 15), DateUtils::addMinutes($baseLine, 30)),
//                            new TimeSeriesRange("Heartrate", DateUtils::addMinutes($baseLine, 40), DateUtils::addMinutes($baseLine, 60))
//                    )));
//
//            assertThat(timeSeriesDetails.getId())
//                    .isEqualTo(documentId);
//            assertThat(timeSeriesDetails.getValues())
//                    .hasSize(1);
//            assertThat(timeSeriesDetails.getValues().get("Heartrate"))
//                    .hasSize(3);
//
//            TimeSeriesRangeResult range = timeSeriesDetails.getValues().get("Heartrate").get(0);
//
//            assertThat(range.getFrom())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 5));
//            assertThat(range.getTo())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 10));
//
//            assertThat(range.getEntries())
//                    .hasSize(31);
//            assertThat(range.getEntries()[0].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 5));
//            assertThat(range.getEntries()[30].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 10));
//
//            range = timeSeriesDetails.getValues().get("Heartrate").get(1);
//
//            assertThat(range.getFrom())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 15));
//            assertThat(range.getTo())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 30));
//
//            assertThat(range.getEntries())
//                    .hasSize(91);
//            assertThat(range.getEntries()[0].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 15));
//            assertThat(range.getEntries()[90].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 30));
//
//            range = timeSeriesDetails.getValues().get("Heartrate").get(2);
//
//            assertThat(range.getFrom())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 40));
//            assertThat(range.getTo())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 60));
//
//            assertThat(range.getEntries())
//                    .hasSize(121);
//            assertThat(range.getEntries()[0].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 40));
//            assertThat(range.getEntries()[120].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 60));
//        }
//    }
//
//    @Test
//    public function canGetMultipleTimeSeriesInSingleRequest(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            String documentId = "users/ayende";
//
//            try (IDocumentSession session = $store->openSession()) {
//                $session->store(new User(), documentId);
//                $session->saveChanges();
//            }
//
//            // append
//
//            Date baseLine = DateUtils.truncate(new Date(), Calendar.DAY_OF_MONTH);
//
//            TimeSeriesOperation timeSeriesOp = new TimeSeriesOperation("Heartrate");
//
//            for (int i = 0; i <= 10; i++) {
//                $timeSeriesOp->append(
//                        new AppendOperation(
//                                DateUtils::addMinutes($baseLine, i * 10), [ 72 ], "watches/fitbit"));
//            }
//
//            TimeSeriesBatchOperation timeSeriesBatch = new TimeSeriesBatchOperation(documentId, timeSeriesOp);
//
//            $store->operations()->send(timeSeriesBatch);
//
//            timeSeriesOp = new TimeSeriesOperation("BloodPressure");
//
//            for (int i = 0; i <= 10; i++) {
//                $timeSeriesOp->append(
//                        new AppendOperation(
//                                DateUtils::addMinutes($baseLine, i * 10),
//                                [ 80 ]
//                        )
//                );
//            }
//
//            timeSeriesBatch = new TimeSeriesBatchOperation(documentId, timeSeriesOp);
//
//            $store->operations()->send(timeSeriesBatch);
//
//            timeSeriesOp = new TimeSeriesOperation("Temperature");
//
//            for (int i = 0; i <= 10; i++) {
//                $timeSeriesOp->append(
//                        new AppendOperation(
//                                DateUtils::addMinutes($baseLine, i * 10),
//                                [ 37 + i * 0.15 ]
//                        )
//                );
//            }
//
//            timeSeriesBatch = new TimeSeriesBatchOperation(documentId, timeSeriesOp);
//
//            $store->operations()->send(timeSeriesBatch);
//
//            // get ranges from multiple time series in a single request
//
//            TimeSeriesDetails timeSeriesDetails = $store->operations()->send(new GetMultipleTimeSeriesOperation(documentId,
//                    Arrays.asList(
//                            new TimeSeriesRange("Heartrate", baseLine, DateUtils::addMinutes($baseLine, 15)),
//                            new TimeSeriesRange("Heartrate", DateUtils::addMinutes($baseLine, 30), DateUtils::addMinutes($baseLine, 45)),
//                            new TimeSeriesRange("BloodPressure", baseLine, DateUtils::addMinutes($baseLine, 30)),
//                            new TimeSeriesRange("BloodPressure", DateUtils::addMinutes($baseLine, 60), DateUtils::addMinutes($baseLine, 90)),
//                            new TimeSeriesRange("Temperature", baseLine, DateUtils.addDays(baseLine, 1))
//                    )));
//
//            assertThat(timeSeriesDetails.getId())
//                    .isEqualTo("users/ayende");
//            assertThat(timeSeriesDetails.getValues())
//                    .hasSize(3);
//
//            assertThat(timeSeriesDetails.getValues().get("Heartrate"))
//                    .hasSize(2);
//
//            TimeSeriesRangeResult range = timeSeriesDetails.getValues().get("Heartrate").get(0);
//            assertThat(range.getFrom())
//                    .isEqualTo(baseLine);
//            assertThat(range.getTo())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 15));
//
//            assertThat(range.getEntries())
//                    .hasSize(2);
//
//            assertThat(range.getEntries()[0].getTimestamp())
//                    .isEqualTo(baseLine);
//            assertThat(range.getEntries()[1].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 10));
//
//            assertThat(range.getTotalResults())
//                    .isNull();
//
//            range = timeSeriesDetails.getValues().get("Heartrate").get(1);
//
//            assertThat(range.getFrom())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 30));
//            assertThat(range.getTo())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 45));
//
//            assertThat(range.getEntries())
//                    .hasSize(2);
//            assertThat(range.getEntries()[0].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 30));
//            assertThat(range.getEntries()[1].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 40));
//
//            assertThat(range.getTotalResults())
//                    .isNull();
//
//            assertThat(timeSeriesDetails.getValues().get("BloodPressure"))
//                    .hasSize(2);
//
//            range = timeSeriesDetails.getValues().get("BloodPressure").get(0);
//
//            assertThat(range.getFrom())
//                    .isEqualTo(baseLine);
//            assertThat(range.getTo())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 30));
//
//            assertThat(range.getEntries())
//                    .hasSize(4);
//
//            assertThat(range.getEntries()[0].getTimestamp())
//                    .isEqualTo(baseLine);
//            assertThat(range.getEntries()[3].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 30));
//
//            assertThat(range.getTotalResults())
//                    .isNull();
//
//            range = timeSeriesDetails.getValues().get("BloodPressure").get(1);
//
//            assertThat(range.getFrom())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 60));
//            assertThat(range.getTo())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 90));
//
//            assertThat(range.getEntries())
//                    .hasSize(4);
//
//            assertThat(range.getEntries()[0].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 60));
//            assertThat(range.getEntries()[3].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 90));
//
//            assertThat(range.getTotalResults())
//                    .isNull();
//
//            assertThat(timeSeriesDetails.getValues().get("Temperature"))
//                    .hasSize(1);
//
//            range = timeSeriesDetails.getValues().get("Temperature").get(0);
//
//            assertThat(range.getFrom())
//                    .isEqualTo(baseLine);
//            assertThat(range.getTo())
//                    .isEqualTo(DateUtils.addDays(baseLine, 1));
//
//            assertThat(range.getEntries())
//                    .hasSize(11);
//
//            assertThat(range.getEntries()[0].getTimestamp())
//                    .isEqualTo(baseLine);
//            assertThat(range.getEntries()[10].getTimestamp())
//                    .isEqualTo(DateUtils::addMinutes($baseLine, 100));
//
//            assertThat(range.getTotalResults())
//                    .isEqualTo(11); // full range
//        }
//    }
//
//    @Test
//    public function shouldThrowOnNullOrEmptyRanges(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            String documentId = "users/ayende";
//
//            try (IDocumentSession session = $store->openSession()) {
//                $session->store(new User(), documentId);
//                $session->saveChanges();
//            }
//
//            Date baseLine = DateUtils.truncate(new Date(), Calendar.DAY_OF_MONTH);
//
//            TimeSeriesOperation timeSeriesOp = new TimeSeriesOperation("Heartrate");
//
//            for (int i = 0; i <= 10; i++) {
//                $timeSeriesOp->append(
//                        new AppendOperation(
//                                DateUtils::addMinutes($baseLine, i * 10), [ 72 ], "watches/fitbit"));
//            }
//
//            TimeSeriesBatchOperation timeSeriesBatch = new TimeSeriesBatchOperation(documentId, timeSeriesOp);
//
//            $store->operations()->send(timeSeriesBatch);
//
//            assertThatThrownBy(() -> {
//                $store->operations()->send(new GetTimeSeriesOperation("users/ayende", null));
//            })
//                    .isInstanceOf(IllegalArgumentException.class);
//
//            assertThatThrownBy(() -> {
//                $store->operations()->send(new GetMultipleTimeSeriesOperation("users/ayende", new ArrayList<>()));
//            })
//                    .isInstanceOf(IllegalArgumentException.class);
//        }
//    }
//
//    @Test
//    public function getMultipleTimeSeriesShouldThrowOnMissingNameFromRange(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            String documentId = "users/ayende";
//
//            try (IDocumentSession session = $store->openSession()) {
//                $session->store(new User(), documentId);
//                $session->saveChanges();
//            }
//
//            Date baseLine = DateUtils.truncate(new Date(), Calendar.DAY_OF_MONTH);
//
//            TimeSeriesOperation timeSeriesOp = new TimeSeriesOperation("Heartrate");
//
//            for (int i = 0; i <= 10; i++) {
//                $timeSeriesOp->append(
//                        new AppendOperation(
//                                DateUtils::addMinutes($baseLine, i * 10), new double[]{72}, "watches/fitbit"));
//            }
//
//            TimeSeriesBatchOperation timeSeriesBatch = new TimeSeriesBatchOperation(documentId, timeSeriesOp);
//
//            $store->operations()->send(timeSeriesBatch);
//
//            assertThatThrownBy(() -> {
//                $store->operations()->send(new GetMultipleTimeSeriesOperation("users/ayende",
//                        Collections.singletonList(
//                                new TimeSeriesRange(null, baseLine, null)
//                        )));
//            })
//                    .isInstanceOf(IllegalArgumentException.class)
//                    .hasMessageContaining("Name cannot be null or empty");
//        }
//    }
//
//    @Test
//    public function getTimeSeriesShouldThrowOnMissingName(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            String documentId = "users/ayende";
//
//            try (IDocumentSession session = $store->openSession()) {
//                $session->store(new User(), documentId);
//                $session->saveChanges();
//            }
//
//            Date baseLine = DateUtils.truncate(new Date(), Calendar.DAY_OF_MONTH);
//
//            TimeSeriesOperation timeSeriesOp = new TimeSeriesOperation("Heartrate");
//
//            for (int i = 0; i <= 10; i++) {
//                $timeSeriesOp->append(
//                        new AppendOperation(
//                                DateUtils::addMinutes($baseLine, i * 10), new double[]{72}, "watches/fitbit"));
//            }
//
//            TimeSeriesBatchOperation timeSeriesBatch = new TimeSeriesBatchOperation(documentId, timeSeriesOp);
//
//            $store->operations()->send(timeSeriesBatch);
//
//            assertThatThrownBy(() -> {
//                $store->operations()->send(new GetTimeSeriesOperation("users/ayende", "", baseLine, DateUtils.addYears(baseLine, 10)));
//            })
//                    .hasMessageContaining("Timeseries cannot be null or empty");
//        }
//    }

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
