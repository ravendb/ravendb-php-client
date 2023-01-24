<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesTypedSessionTest;

use DateTime;
use RavenDB\Documents\Operations\TimeSeries\ConfigureTimeSeriesOperation;
use RavenDB\Documents\Operations\TimeSeries\RawTimeSeriesPolicy;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesCollectionConfiguration;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesConfiguration;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesPolicy;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesEntry;
use RavenDB\Primitives\TimeValue;
use RavenDB\Type\Duration;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\DisableOnPullRequestCondition;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class TimeSeriesTypedSessionTest extends RemoteTestBase
{

//    @Test
//    public function canRegisterTimeSeries(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            $store->timeSeries()->register($user->class, StockPrice.class);
//            $store->timeSeries()->register("Users", "HeartRateMeasures", new String[] { "heartRate" });
//
//            TimeSeriesConfiguration updated = $store->maintenance().server()->send(
//                    new GetDatabaseRecordOperation(store.getDatabase())).getTimeSeries();
//
//            // this method is case insensitive
//            String[] heartRate = updated.getNames("users", "HeartRateMeasures");
//            assertThat(heartRate)
//                    .hasSize(1);
//            assertThat(heartRate[0])
//                    .isEqualTo("heartRate");
//
//            String[] stock = updated.getNames("users", "StockPrices");
//            assertThat(stock)
//                    .hasSize(5)
//                    .containsExactly("open", "close", "high", "low", "volume");
//        }
//    }
//
//    @Test
//    public function canRegisterTimeSeriesWithCustomName(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            $store->timeSeries()->register($user->class, HeartRateMeasureWithCustomName.class, "cn");
//
//            TimeSeriesConfiguration updated = $store->maintenance().server()->send(
//                    new GetDatabaseRecordOperation(store.getDatabase())).getTimeSeries();
//
//            // this method is case insensitive
//            String[] heartRate = updated.getNames("users", "cn");
//            assertThat(heartRate)
//                    .hasSize(1);
//            assertThat(heartRate[0])
//                    .isEqualTo("HR");
//        }
//    }
//
//    @Test
//    public function canRegisterTimeSeriesForOtherDatabase(): void {
//        try (IDocumentStore store1 = getDocumentStore()) {
//            try (IDocumentStore store2 = getDocumentStore()) {
//                store1.timeSeries().forDatabase(store2.getDatabase())->register($user->class, StockPrice.class);
//                store1.timeSeries().forDatabase(store2.getDatabase())->register("Users", "HeartRateMeasures", new String[] { "HeartRate" });
//
//                TimeSeriesConfiguration updated = store1.maintenance().server()->send(new GetDatabaseRecordOperation(store2.getDatabase())).getTimeSeries();
//
//                assertThat(updated)
//                        .isNotNull();
//
//                String[] heartrate = updated.getNames("users", "HeartRateMeasures");
//                assertThat(heartrate)
//                        .hasSize(1);
//                assertThat(heartrate[0])
//                        .isEqualTo("HeartRate");
//
//                String[] stock = updated.getNames("users", "StockPrices");
//                assertThat(stock)
//                        .hasSize(5);
//                assertThat(stock[0])
//                        .isEqualTo("open");
//                assertThat(stock[1])
//                        .isEqualTo("close");
//                assertThat(stock[2])
//                        .isEqualTo("high");
//                assertThat(stock[3])
//                        .isEqualTo("low");
//                assertThat(stock[4])
//                        .isEqualTo("volume");
//            }
//        }
//    }

    public function testCanCreateSimpleTimeSeries(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, "users/ayende");

                $heartRateMeasure = new HeartRateMeasure();
                $heartRateMeasure->setHeartRate(59);
                $ts = $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende");
                $ts->append($baseLine, $heartRateMeasure, "watches/fitbit");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende")
                    ->get()[0];

                $this->assertEquals(59, $val->getValue()->getHeartRate());
                $this->assertEquals("watches/fitbit", $val->getTag());
                $this->assertEquals($baseLine, $val->getTimestamp());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanCreateSimpleTimeSeriesAsync(): void
    {
        $store = $this->getDocumentStore();
        try {

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, "users/ayende");

                $heartRateMeasure = new HeartRateMeasure();
                $heartRateMeasure->setHeartRate(59);

                $measure = new TypedTimeSeriesEntry();
                $measure->setTimestamp(DateUtils::addMinutes($baseLine, 1));
                $measure->setValue($heartRateMeasure);
                $measure->setTag("watches/fitbit");

                $ts = $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende");
                $ts->appendEntry($measure);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende")
                    ->get()[0];

                $this->assertEquals(59, $val->getValue()->getHeartRate());
                $this->assertEquals("watches/fitbit", $val->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $val->getTimestamp());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanCreateSimpleTimeSeries2(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, "users/ayende");

                $tsf = $session->timeSeriesFor("users/ayende", "HeartRateMeasures");
                $tsf->append(DateUtils::addMinutes($baseLine, 1), 59, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 2), 60, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 2), 61, "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende")
                    ->get();
                $this->assertCount(2, $val);

                $this->assertEquals(59, $val[0]->getValue()->getHeartRate());
                $this->assertEquals(61, $val[1]->getValue()->getHeartRate());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanRequestNonExistingTimeSeriesRange(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, "users/ayende");

                $tsf = $session->timeSeriesFor("users/ayende", "HeartRateMeasures");
                $tsf->append($baseLine, 58, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 10), 60, "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende")
                    ->get(DateUtils::addMinutes($baseLine, -10), DateUtils::addMinutes($baseLine, -5));

                $this->assertEmpty($vals);

                $vals = $session->timeSeriesFor(HeartRateMeasure::class, "users/ayende")
                    ->get(DateUtils::addMinutes($baseLine, 5), DateUtils::addMinutes($baseLine, 9));

                $this->assertEmpty($vals);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetTimeSeriesNames(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $session->store(new User(), "users/karmel");
                $heartRateMeasure = new HeartRateMeasure();
                $heartRateMeasure->setHeartRate(66);
                $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/karmel")
                    ->append(new DateTime(), $heartRateMeasure, "MyHeart");

                $stockPrice = new StockPrice();
                $stockPrice->setOpen(66);
                $stockPrice->setClose(55);
                $stockPrice->setHigh(113.4);
                $stockPrice->setLow(52.4);
                $stockPrice->setVolume(15472);
                $session->typedTimeSeriesFor(StockPrice::class, "users/karmel")
                    ->append(new DateTime(), $stockPrice);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/karmel");
                $tsNames = $session->advanced()->getTimeSeriesFor($user);
                $this->assertCount(2, $tsNames);

                // should be sorted
                $this->assertEquals("HeartRateMeasures", $tsNames[0]);
                $this->assertEquals("StockPrices", $tsNames[1]);

                $heartRateMeasures = $session->typedTimeSeriesFor(HeartRateMeasure::class, $user)
                    ->get()[0];
                $this->assertEquals(66, $heartRateMeasures->getValue()->getHeartRate());

                $stockPriceEntry = $session->typedTimeSeriesFor(StockPrice::class, $user)
                    ->get()[0];
                $this->assertEquals(66, $stockPriceEntry->getValue()->getOpen());
                $this->assertEquals(55, $stockPriceEntry->getValue()->getClose());
                $this->assertEquals(113.4, $stockPriceEntry->getValue()->getHigh());
                $this->assertEquals(52.4, $stockPriceEntry->getValue()->getLow());
                $this->assertEquals(15472, $stockPriceEntry->getValue()->getVolume());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

//    public function canQueryTimeSeriesAggregation_DeclareSyntax_AllDocsQuery(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());
//
//            try (IDocumentSession session = $store->openSession()) {
//                $user = new User();
//                $session->store($user, "users/ayende");
//
//                ISessionDocumentTypedTimeSeries<HeartRateMeasure> tsf = $session->timeSeriesFor(HeartRateMeasure::class, "users/ayende");
//                String tag = "watches/fitbit";
//                HeartRateMeasure m = new HeartRateMeasure();
//                m.setHeartRate(59);
//
//                $tsf->append(DateUtils::addMinutes($baseLine, 61), m, tag);
//
//                m.setHeartRate(79);
//                $tsf->append(DateUtils::addMinutes($baseLine, 62), m, tag);
//
//                m.setHeartRate(69);
//                $tsf->append(DateUtils::addMinutes($baseLine, 63), m, tag);
//
//                $session->saveChanges();
//            }
//
//            try (IDocumentSession session = $store->openSession()) {
//                IRawDocumentQuery<TimeSeriesAggregationResult> query =
//                        $session->advanced().rawQuery(TimeSeriesAggregationResult.class,
//                                "declare timeseries out(u)\n" +
//                        "    {\n" +
//                        "        from u.HeartRateMeasures between $start and $end\n" +
//                        "        group by 1h\n" +
//                        "        select min(), max(), first(), last()\n" +
//                        "    }\n" +
//                        "    from @all_docs as u\n" +
//                        "    where id() == 'users/ayende'\n" +
//                        "    select out(u)")
//                        .addParameter("start", baseLine)
//                        .addParameter("end", DateUtils.addDays($baseLine, 1));
//
//                TypedTimeSeriesAggregationResult<HeartRateMeasure> agg = query.first().asTypedResult(HeartRateMeasure::class);
//
//                assertThat(agg.getCount())
//                        .isEqualTo(3);
//                assertThat(agg.getResults())
//                        .hasSize(1);
//
//                TypedTimeSeriesRangeAggregation<HeartRateMeasure> val = agg.getResults()[0];
//                assertThat($val->getFirst()->getHeartRate())
//                        .isEqualTo(59);
//                assertThat($val->getMin()->getHeartRate())
//                        .isEqualTo(59);
//
//                assertThat($val->getLast()->getHeartRate())
//                        .isEqualTo(69);
//                assertThat($val->getMax()->getHeartRate())
//                        .isEqualTo(79);
//
//                assertThat($val->getFrom())
//                        .isEqualTo(DateUtils::addMinutes($baseLine, 60));
//                assertThat($val->getTo())
//                        .isEqualTo(DateUtils::addMinutes($baseLine, 120));
//            }
//        }
//    }
//
//    @Test
//    public function canQueryTimeSeriesAggregation_NoSelectOrGroupBy(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());
//
//            try (IDocumentSession session = $store->openSession()) {
//                for ($i = 1; $i <= 3; $i++) {
//                    String id = "people/" + i;
//
//                    $user = new User();
//                    $user->setName("Oren");
//                    $user->setAge(i * 30);
//
//                    $session->store($user, id);
//
//                    ISessionDocumentTypedTimeSeries<HeartRateMeasure> tsf = $session->timeSeriesFor(HeartRateMeasure::class, id);
//                    HeartRateMeasure m = new HeartRateMeasure();
//                    m.setHeartRate(59);
//
//                    $tsf->append(DateUtils::addMinutes($baseLine, 61), m, "watches/fitbit");
//
//                    m.setHeartRate(79);
//                    $tsf->append(DateUtils::addMinutes($baseLine, 62), m,"watches/fitbit");
//
//                    m.setHeartRate(69);
//                    $tsf->append(DateUtils::addMinutes($baseLine, 63), m, "watches/apple");
//
//                    m.setHeartRate(159);
//                    $tsf->append(DateUtils.addMonths(DateUtils::addMinutes($baseLine, 61), 1), m, "watches/fitbit");
//
//                    m.setHeartRate(179);
//                    $tsf->append(DateUtils.addMonths(DateUtils::addMinutes($baseLine, 62), 1), m, "watches/apple");
//
//                    m.setHeartRate(169);
//                    $tsf->append(DateUtils.addMonths(DateUtils::addMinutes($baseLine, 63), 1), m, "watches/fitbit");
//                }
//
//                $session->saveChanges();
//            }
//
//            try (IDocumentSession session = $store->openSession()) {
//                IRawDocumentQuery<TimeSeriesRawResult> query = $session->advanced().rawQuery(TimeSeriesRawResult.class, "declare timeseries out(x)\n" +
//                        "{\n" +
//                        "    from x.HeartRateMeasures between $start and $end\n" +
//                        "}\n" +
//                        "from Users as doc\n" +
//                        "where doc.age > 49\n" +
//                        "select out(doc)")
//                        .addParameter("start", baseLine)
//                        .addParameter("end", DateUtils.addMonths($baseLine, 2));
//
//                List<TimeSeriesRawResult> result = query.toList();
//
//                assertThat(result)
//                        .hasSize(2);
//
//                for ($i = 0; $i < 2; $i++) {
//                    TimeSeriesRawResult aggRaw = result.get($i);
//                    TypedTimeSeriesRawResult<HeartRateMeasure> agg = aggRaw.asTypedResult(HeartRateMeasure::class);
//
//                    assertThat(agg.getResults())
//                            .hasSize(6);
//
//                    TypedTimeSeriesEntry<HeartRateMeasure> val = agg.getResults()[0];
//
//                    assertThat($val->getValues())
//                            .hasSize(1);
//                    assertThat($val->getValue()->getHeartRate())
//                            .isEqualTo(59);
//                    assertThat($val->getTag())
//                            .isEqualTo("watches/fitbit");
//                    assertThat($val->getTimestamp())
//                            .isEqualTo(DateUtils::addMinutes($baseLine, 61));
//
//                    val = agg.getResults()[1];
//
//                    assertThat($val->getValues())
//                            .hasSize(1);
//                    assertThat($val->getValue()->getHeartRate())
//                            .isEqualTo(79);
//                    assertThat($val->getTag())
//                            .isEqualTo("watches/fitbit");
//                    assertThat($val->getTimestamp())
//                            .isEqualTo(DateUtils::addMinutes($baseLine, 62));
//
//                    val = agg.getResults()[2];
//
//                    assertThat($val->getValues())
//                            .hasSize(1);
//                    assertThat($val->getValue()->getHeartRate())
//                            .isEqualTo(69);
//                    assertThat($val->getTag())
//                            .isEqualTo("watches/apple");
//                    assertThat($val->getTimestamp())
//                            .isEqualTo(DateUtils::addMinutes($baseLine, 63));
//
//                    val = agg.getResults()[3];
//
//                    assertThat($val->getValues())
//                            .hasSize(1);
//                    assertThat($val->getValue()->getHeartRate())
//                            .isEqualTo(159);
//                    assertThat($val->getTag())
//                            .isEqualTo("watches/fitbit");
//                    assertThat($val->getTimestamp())
//                            .isEqualTo(DateUtils.addMonths(DateUtils::addMinutes($baseLine, 61), 1));
//
//                    val = agg.getResults()[4];
//
//                    assertThat($val->getValues())
//                            .hasSize(1);
//                    assertThat($val->getValue()->getHeartRate())
//                            .isEqualTo(179);
//                    assertThat($val->getTag())
//                            .isEqualTo("watches/apple");
//                    assertThat($val->getTimestamp())
//                            .isEqualTo(DateUtils.addMonths(DateUtils::addMinutes($baseLine, 62), 1));
//
//                    val = agg.getResults()[5];
//
//                    assertThat($val->getValues())
//                            .hasSize(1);
//                    assertThat($val->getValue()->getHeartRate())
//                            .isEqualTo(169);
//                    assertThat($val->getTag())
//                            .isEqualTo("watches/fitbit");
//                    assertThat($val->getTimestamp())
//                            .isEqualTo(DateUtils.addMonths(DateUtils::addMinutes($baseLine, 63), 1));
//                }
//            }
//        }
//    }

    public function atestCanWorkWithRollupTimeSeries(): void
    {
//        DisableOnPullRequestCondition::evaluateExecutionCondition($this);

        $store = $this->getDocumentStore();
        try {
            $raw = new RawTimeSeriesPolicy(TimeValue::ofHours(24));
            $rawRetentionSeconds = $raw->getRetentionTime()->getValue();

            $p1 = new TimeSeriesPolicy("By6Hours", TimeValue::ofHours(6), TimeValue::ofSeconds($rawRetentionSeconds * 4));
            $p2 = new TimeSeriesPolicy("By1Day", TimeValue::ofDays(1), TimeValue::ofSeconds($rawRetentionSeconds * 5));
            $p3 = new TimeSeriesPolicy("By30Minutes", TimeValue::ofMinutes(30), TimeValue::ofSeconds($rawRetentionSeconds * 2));
            $p4 = new TimeSeriesPolicy("By1Hour", TimeValue::ofMinutes(60), TimeValue::ofSeconds($rawRetentionSeconds * 3));

            $usersConfig = new TimeSeriesCollectionConfiguration();
            $usersConfig->setRawPolicy($raw);
            $usersConfig->setPolicies([$p1, $p2, $p3, $p4]);

            $config = new TimeSeriesConfiguration();
            $config->setCollections(["Users" => $usersConfig]);
            $config->setPolicyCheckFrequency(Duration::ofMillis(100));

            $store->maintenance()->send(new ConfigureTimeSeriesOperation($config));
            $store->timeSeries()->register(User::class, StockPrice::class);

            // please notice we don't modify server time here!

            $now = new DateTime();
            $baseline = DateUtils::addDays($now, -12);

            
            $total = 10000;// (int) Duration.ofDays(12).get(ChronoUnit.SECONDS) / 60;

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Karmel");
                $session->store($user, "users/karmel");
                $ts = $session->typedTimeSeriesFor(StockPrice::class, "users/karmel");

                $entry = new StockPrice();

                for ($i = 0; $i <= $total; $i++) {
                    $entry->setOpen($i);
                    $entry->setClose($i + 100_000);
                    $entry->setHigh($i + 200_000);
                    $entry->setLow($i + 300_000);
                    $entry->setVolume($i + 400_000);
                    $ts->append(DateUtils::addMinutes($baseline, $i), $entry, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            usleep(1500000); // wait for rollup

            $session = $store->openSession();
            try {
//                IRawDocumentQuery<TimeSeriesRawResult> query = $session->advanced().rawQuery(TimeSeriesRawResult.class, "declare timeseries out()\n" +
//                        "{\n" +
//                        "    from StockPrices\n" +
//                        "    between $start and $end\n" +
//                        "}\n" +
//                        "from Users as u\n" +
//                        "select out()")
//                        .addParameter("start", DateUtils.addDays(baseline, -1))
//                        .addParameter("end", DateUtils.addDays(now, 1));
//
//                TimeSeriesRawResult resultRaw = query.single();
//                TypedTimeSeriesRawResult<StockPrice> result = resultRaw.asTypedResult(StockPrice.class);
//
//                assertThat(result.getResults().length)
//                        .isPositive();
//
//                for (TypedTimeSeriesEntry<StockPrice> res : result.getResults()) {
//
//                    if (res.isRollup()) {
//                        assertThat(res.getValues().length)
//                                .isPositive();
//                        assertThat(res->getValue()->getLow())
//                                .isPositive();
//                        assertThat(res->getValue()->getHigh())
//                                .isPositive();
//                    } else {
//                        assertThat(res.getValues())
//                                .hasSize(5);
//                    }
//                }
            } finally {
                $session->close();
            }
//
//            now = new Date();
//
            $session = $store->openSession();
            try {
//                ISessionDocumentRollupTypedTimeSeries<StockPrice> ts = $session->timeSeriesRollupFor(StockPrice.class, "users/karmel", p1.getName());
//                TypedTimeSeriesRollupEntry<StockPrice> a = new TypedTimeSeriesRollupEntry<>(StockPrice.class, new Date());
//                a.getMax().setClose(1);
//                ts.append(a);
//                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
//                ISessionDocumentRollupTypedTimeSeries<StockPrice> ts = $session->timeSeriesRollupFor(StockPrice.class, "users/karmel", p1.getName());
//
//                List<TypedTimeSeriesRollupEntry<StockPrice>> res = Arrays.asList(ts.get(DateUtils.addMilliseconds(now, -1), DateUtils.addDays(now, 1)));
//                assertThat(res)
//                        .hasSize(1);
//                assertThat(res.get(0).getMax().getClose())
//                        .isEqualTo(1);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanWorkWithRollupTimeSeries2(): void
    {
        DisableOnPullRequestCondition::evaluateExecutionCondition($this);

        $store = $this->getDocumentStore();
        try {
            $rawHours = 24;
            $raw = new RawTimeSeriesPolicy(TimeValue::ofHours($rawHours));

            $p1 = new TimeSeriesPolicy("By6Hours", TimeValue::ofHours(6), TimeValue::ofHours($rawHours * 4));
            $p2 = new TimeSeriesPolicy("By1Day", TimeValue::ofDays(1), TimeValue::ofHours($rawHours * 5));
            $p3 = new TimeSeriesPolicy("By30Minutes", TimeValue::ofMinutes(30), TimeValue::ofHours($rawHours * 2));
            $p4 = new TimeSeriesPolicy("By1Hour", TimeValue::ofMinutes(60), TimeValue::ofHours($rawHours * 3));

            $usersConfig = new TimeSeriesCollectionConfiguration();
            $usersConfig->setRawPolicy($raw);
            $usersConfig->setPolicies([$p1, $p2, $p3, $p4]);

            $config = new TimeSeriesConfiguration();
            $config->setPolicyCheckFrequency(Duration::ofMillis(100));
            $config->setCollections(["Users" => $usersConfig]);

            $store->maintenance()->send(new ConfigureTimeSeriesOperation($config));
            $store->timeSeries()->register(User::class, StockPrice::class);

            $now = DateUtils::truncateDayOfMonth(new DateTime());
            $baseline = DateUtils::addDays($now, -12);
            $total = Duration::ofDays(12)->getSeconds() / 60;

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Karmel");
                $session->store($user, "users/karmel");
                $ts = $session->typedTimeSeriesFor(StockPrice::class, "users/karmel");

                $entry = new StockPrice();
                for ($i = 0; $i <= $total; $i++) {
                    $entry->setOpen($i);
                    $entry->setClose($i + 100_000);
                    $entry->setHigh($i + 200_000);
                    $entry->setLow($i + 300_000);
                    $entry->setVolume($i + 400_000);

                    $ts->append(DateUtils::addMinutes($baseline, $i), $entry, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            usleep(1500000); // wait for rollups

            $session = $store->openSession();
            try {
                $ts1 = $session->timeSeriesRollupFor(StockPrice::class, "users/karmel", $p1->getName());
                $r = $ts1->get()[0];
                $this->assertNotNull($r->getFirst());
                $this->assertNotNull($r->getLast());
                $this->assertNotNull($r->getMin());
                $this->assertNotNull($r->getMax());
                $this->assertNotNull($r->getCount());
                $this->assertNotNull($r->getAverage());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testUsingDifferentNumberOfValues_LargeToSmall(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::addDays(DateUtils::truncateDayOfMonth(new DateTime()), -1);

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Karmel");;
                $session->store($user, "users/karmel");

                $big = $session->typedTimeSeriesFor(BigMeasure::class, "users/karmel");
                for ($i = 0; $i < 5; $i++) {
                    $bigMeasure = new BigMeasure();
                    $bigMeasure->setMeasure1($i);
                    $bigMeasure->setMeasure2($i);
                    $bigMeasure->setMeasure3($i);
                    $bigMeasure->setMeasure4($i);
                    $bigMeasure->setMeasure5($i);
                    $bigMeasure->setMeasure6($i);
                    $big->append(DateUtils::addSeconds($baseLine, 3 * $i), $bigMeasure, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $big = $session->timeSeriesFor("users/karmel", "BigMeasures");

                for ($i = 5; $i < 10; $i++) {
                    $big->append(DateUtils::addSeconds(DateUtils::addHours($baseLine, 12), 3 * $i), $i, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $big = $session->typedTimeSeriesFor(BigMeasure::class, "users/karmel")
                    ->get();

                for ($i = 0; $i < 5; $i++) {
                    $m = $big[$i]->getValue();
                    $this->assertEquals($i, $m->getMeasure1());
                    $this->assertEquals($i, $m->getMeasure2());
                    $this->assertEquals($i, $m->getMeasure3());
                    $this->assertEquals($i, $m->getMeasure4());
                    $this->assertEquals($i, $m->getMeasure5());
                    $this->assertEquals($i, $m->getMeasure6());
                }

                for ($i = 5; $i < 10; $i++) {
                    $m = $big[$i]->getValue();
                    $this->assertEquals($i, $m->getMeasure1());
                    $this->assertNan($m->getMeasure2());
                    $this->assertNan($m->getMeasure3());
                    $this->assertNan($m->getMeasure4());
                    $this->assertNan($m->getMeasure5());
                    $this->assertNan($m->getMeasure6());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

//    public function mappingNeedsToContainConsecutiveValuesStartingFromZero(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            assertThatThrownBy(() -> {
//                $store->timeSeries()->register(Company.class, StockPriceWithBadAttributes.class);
//            })
//                    .hasMessageContaining("must contain consecutive values starting from 0");
//        }
//    }

}
