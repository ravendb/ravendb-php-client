<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesTypedSessionTest;

use DateTime;
use Exception;
use RavenDB\Documents\Operations\TimeSeries\ConfigureTimeSeriesOperation;
use RavenDB\Documents\Operations\TimeSeries\RawTimeSeriesPolicy;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesCollectionConfiguration;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesConfiguration;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesPolicy;
use RavenDB\Documents\Queries\TimeSeries\TimeSeriesAggregationResult;
use RavenDB\Documents\Queries\TimeSeries\TimeSeriesRawResult;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesEntry;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesRollupEntry;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesRollupEntryArray;
use RavenDB\Primitives\TimeValue;
use RavenDB\ServerWide\GetDatabaseRecordOperation;
use RavenDB\Type\Duration;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class TimeSeriesTypedSessionTest extends RemoteTestBase
{

    public function testCanRegisterTimeSeries(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->timeSeries()->register(User::class, StockPrice::class);
            $store->timeSeries()->register("Users", "HeartRateMeasures", [ "heartRate" ]);

            /** @var TimeSeriesConfiguration $updated */
            $updated = $store->maintenance()->server()->send(
                    new GetDatabaseRecordOperation($store->getDatabase()))->getTimeSeries();

            // this method is case insensitive
            $heartRate = $updated->getNames("users", "HeartRateMeasures");
            $this->assertCount(1, $heartRate);

            $this->assertEquals("heartRate", $heartRate[0]);

            $stock = $updated->getNames("users", "StockPrices");
            $this->assertCount(5, $stock);
            $this->assertEquals(["open", "close", "high", "low", "volume"], $stock);
        } finally {
            $store->close();
        }
    }

    public function testCanRegisterTimeSeriesWithCustomName(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->timeSeries()->register(User::class, HeartRateMeasureWithCustomName::class, "cn");

            /** @var TimeSeriesConfiguration $updated */
            $updated = $store->maintenance()->server()->send(
                    new GetDatabaseRecordOperation($store->getDatabase()))->getTimeSeries();

            // this method is case insensitive
            $heartRate = $updated->getNames("users", "cn");
            $this->assertCount(1, $heartRate);
            $this->assertEquals("HR", $heartRate[0]);
        } finally {
            $store->close();
        }
    }


    public function testCanRegisterTimeSeriesForOtherDatabase(): void
    {
        $store1 = $this->getDocumentStore();
        try {
            $store2 = $this->getDocumentStore();
            try {
                $store1->timeSeries()->forDatabase($store2->getDatabase())->register(User::class, StockPrice::class);
                $store1->timeSeries()->forDatabase($store2->getDatabase())->register("Users", "HeartRateMeasures", [ "HeartRate" ]);

                $updated = $store1->maintenance()->server()->send(new GetDatabaseRecordOperation($store2->getDatabase()))->getTimeSeries();

                $this->assertNotNull($updated);

                $heartrate = $updated->getNames("users", "HeartRateMeasures");
                $this->assertCount(1, $heartrate);
                $this->assertEquals("HeartRate", $heartrate[0]);

                $stock = $updated->getNames("users", "StockPrices");
                $this->assertCount(5, $stock);
                $this->assertEquals("open", $stock[0]);
                $this->assertEquals("close", $stock[1]);
                $this->assertEquals("high", $stock[2]);
                $this->assertEquals("low", $stock[3]);
                $this->assertEquals("volume", $stock[4]);
            } finally {
                $store2->close();
            }
        } finally {
            $store1->close();
        }
    }

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

    public function testCanQueryTimeSeriesAggregation_DeclareSyntax_AllDocsQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, "users/ayende");

                $tsf = $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende");
                $tag = "watches/fitbit";
                $m = new HeartRateMeasure();
                $m->setHeartRate(59);

                $tsf->append(DateUtils::addMinutes($baseLine, 61), $m, $tag);

                $m->setHeartRate(79);
                $tsf->append(DateUtils::addMinutes($baseLine, 62), $m, $tag);

                $m->setHeartRate(69);
                $tsf->append(DateUtils::addMinutes($baseLine, 63), $m, $tag);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query =
                        $session->advanced()->rawQuery(TimeSeriesAggregationResult::class,
                                "declare timeseries out(u)\n" .
                        "    {\n" .
                        "        from u.HeartRateMeasures between \$start and \$end\n" .
                        "        group by 1h\n" .
                        "        select min(), max(), first(), last()\n" .
                        "    }\n" .
                        "    from @all_docs as u\n" .
                        "    where id() == 'users/ayende'\n" .
                        "    select out(u)")
                        ->addParameter("start", $baseLine)
                        ->addParameter("end", DateUtils::addDays($baseLine, 1));

                $agg = $query->first()->asTypedResult(HeartRateMeasure::class);

                $this->assertEquals(3, $agg->getCount());
                $this->assertCount(1, $agg->getResults());

                $val = $agg->getResults()[0];
                $this->assertEquals(59, $val->getFirst()->getHeartRate());
                $this->assertEquals(59, $val->getMin()->getHeartRate());

                $this->assertEquals(69, $val->getLast()->getHeartRate());
                $this->assertEquals(79, $val->getMax()->getHeartRate());

                $this->assertEquals(DateUtils::addMinutes($baseLine, 60), $val->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 120), $val->getTo());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanQueryTimeSeriesAggregation_NoSelectOrGroupBy(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());



            $session = $store->openSession();
            try {
                for ($i = 1; $i <= 3; $i++) {
                    $id = "people/" . $i;

                    $user = new User();
                    $user->setName("Oren");
                    $user->setAge($i * 30);

                    $session->store($user, $id);

                    $tsf = $session->typedTimeSeriesFor(HeartRateMeasure::class, $id);
                    $m = new HeartRateMeasure();
                    $m->setHeartRate(59);

                    $tsf->append(DateUtils::addMinutes($baseLine, 61), $m, "watches/fitbit");

                    $m->setHeartRate(79);
                    $tsf->append(DateUtils::addMinutes($baseLine, 62), $m,"watches/fitbit");

                    $m->setHeartRate(69);
                    $tsf->append(DateUtils::addMinutes($baseLine, 63), $m, "watches/apple");

                    $m->setHeartRate(159);
                    $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 61), 1), $m, "watches/fitbit");

                    $m->setHeartRate(179);
                    $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 62), 1), $m, "watches/apple");

                    $m->setHeartRate(169);
                    $tsf->append(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 63), 1), $m, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->advanced()->rawQuery(TimeSeriesRawResult::class, "declare timeseries out(x)\n" .
                        "{\n" .
                        "    from x.HeartRateMeasures between \$start and \$end\n" .
                        "}\n" .
                        "from Users as doc\n" .
                        "where doc.age > 49\n" .
                        "select out(doc)")
                        ->addParameter("start", $baseLine)
                        ->addParameter("end", DateUtils::addMonths($baseLine, 2));

                $result = $query->toList();

                $this->assertCount(2, $result);

                for ($i = 0; $i < 2; $i++) {
                    $aggRaw = $result[$i];
                    $agg = $aggRaw->asTypedResult(HeartRateMeasure::class);

                    $this->assertCount(6, $agg->getResults());

                    $val = $agg->getResults()[0];

                    $this->assertCount(1, $val->getValues());
                    $this->assertEquals(59, $val->getValue()->getHeartRate());
                    $this->assertEquals("watches/fitbit", $val->getTag());
                    $this->assertEquals(DateUtils::addMinutes($baseLine, 61), $val->getTimestamp());

                    $val = $agg->getResults()[1];

                    $this->assertCount(1, $val->getValues());
                    $this->assertEquals(79, $val->getValue()->getHeartRate());
                    $this->assertEquals("watches/fitbit", $val->getTag());
                    $this->assertEquals(DateUtils::addMinutes($baseLine, 62), $val->getTimestamp());

                    $val = $agg->getResults()[2];

                    $this->assertCount(1, $val->getValues());
                    $this->assertEquals(69, $val->getValue()->getHeartRate());
                    $this->assertEquals("watches/apple", $val->getTag());
                    $this->assertEquals(DateUtils::addMinutes($baseLine, 63), $val->getTimestamp());

                    $val = $agg->getResults()[3];

                    $this->assertCount(1, $val->getValues());
                    $this->assertEquals(159, $val->getValue()->getHeartRate());
                    $this->assertEquals("watches/fitbit", $val->getTag());
                    $this->assertEquals(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 61), 1), $val->getTimestamp());

                    $val = $agg->getResults()[4];

                    $this->assertCount(1, $val->getValues());
                    $this->assertEquals(179, $val->getValue()->getHeartRate());
                    $this->assertEquals("watches/apple", $val->getTag());
                    $this->assertEquals(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 62), 1), $val->getTimestamp());

                    $val = $agg->getResults()[5];

                    $this->assertCount(1, $val->getValues());
                    $this->assertEquals(169, $val->getValue()->getHeartRate());
                    $this->assertEquals("watches/fitbit", $val->getTag());
                    $this->assertEquals(DateUtils::addMonths(DateUtils::addMinutes($baseLine, 63), 1), $val->getTimestamp());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanWorkWithRollupTimeSeries(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailable($this);

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

            $total =  Duration::ofDays(12)->getSeconds() / 60;

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
                $query = $session->advanced()->rawQuery(TimeSeriesRawResult::class, "declare timeseries out()\n" .
                        "{\n" .
                        "    from StockPrices\n" .
                        '    between $start and $end' . "\n" .
                        "}\n" .
                        "from Users as u\n" .
                        "select out()")
                        ->addParameter("start", DateUtils::addDays($baseline, -1))
                        ->addParameter("end", DateUtils::addDays($now, 1));

                /** @var TimeSeriesRawResult $resultRaw */
                $resultRaw = $query->single();
                $result = $resultRaw->asTypedResult(StockPrice::class);

                $this->assertGreaterThan(0, count($result->getResults()));

                /** @var TypedTimeSeriesEntry<StockPrice> $res */
                foreach ($result->getResults() as $res) {
                    if ($res->isRollup()) {
                        $this->assertGreaterThan(0, count($res->getValues()));
                        $this->assertGreaterThan(0, $res->getValue()->getLow());
                        $this->assertGreaterThan(0, $res->getValue()->getHigh());
                    } else {
                        $this->assertCount(5, $res->getValues());
                    }
                }
            } finally {
                $session->close();
            }

            $now = new DateTime();

            $session = $store->openSession();
            try {
                $ts = $session->timeSeriesRollupFor(StockPrice::class, "users/karmel", $p1->getName());
                $a = new TypedTimeSeriesRollupEntry(StockPrice::class, new DateTime());
                $a->getMax()->setClose(1);
                $ts->appendEntry($a);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $ts = $session->timeSeriesRollupFor(StockPrice::class, "users/karmel", $p1->getName());

                /** @var TypedTimeSeriesRollupEntryArray<StockPrice> $res */
                $res = $ts->get(DateUtils::addMilliseconds($now, -1), DateUtils::addDays($now, 1));
                $this->assertCount(1, $res);

                $this->assertEquals(1, $res[0]->getMax()->getClose());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanWorkWithRollupTimeSeries2(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailable($this);

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

    public function testMappingNeedsToContainConsecutiveValuesStartingFromZero(): void
    {
        $store = $this->getDocumentStore();
        try {
            try {
                $store->timeSeries()->register(Company::class, StockPriceWithBadAttributes::class);

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("must contain consecutive values starting from 0", $exception->getMessage());
            }
        } finally {
            $store->close();
        }
    }

}
