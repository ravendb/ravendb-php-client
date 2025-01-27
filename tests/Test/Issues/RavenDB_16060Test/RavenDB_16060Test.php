<?php

namespace tests\RavenDB\Test\Issues\RavenDB_16060Test;

use DateTime;
use RavenDB\Documents\Operations\TimeSeries\ConfigureTimeSeriesOperation;
use RavenDB\Documents\Operations\TimeSeries\RawTimeSeriesPolicy;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesCollectionConfiguration;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesCollectionConfigurationMap;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesConfiguration;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesPolicy;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeType;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesEntryArray;
use RavenDB\Documents\Session\TimeSeries\TypedTimeSeriesRollupEntryArray;
use RavenDB\Primitives\TimeValue;
use RavenDB\Type\Duration;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesTypedSessionTest\StockPrice;

class RavenDB_16060Test extends RemoteTestBase
{
    public function setUp(): void
    {
        parent::setUp();
        TestRunGuard::disableTestIfLicenseNotAvailable($this);
    }

    public function testCanIncludeTypedTimeSeries(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");

                $session->store($user, "users/ayende");

                $ts = $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende");
                $ts->append($baseLine, HeartRateMeasure::create(59), "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $items = $session
                        ->query(get_class($user))
                        ->include(function($x) { return $x->includeTimeSeries("heartRateMeasures"); })
                        ->toList();

                foreach ($items as $item) {
                    /** @var TypedTimeSeriesEntryArray<HeartRateMeasure> $timeseries */
                    $timeseries = $session->typedTimeSeriesFor(HeartRateMeasure::class, $item->getId(), "heartRateMeasures")
                            ->get();

                    $this->assertCount(1, $timeseries);
                    $this->assertEquals(59, $timeseries[0]->getValue()->getHeartRate());
                }

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanServeTimeSeriesFromCache_Typed(): void
    {
        //RavenDB-16136
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $id = "users/gabor";

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Gabor");
                $session->store($user, $id);

                $ts = $session->typedTimeSeriesFor(HeartRateMeasure::class, $id);

                $ts->append($baseLine, HeartRateMeasure::create(59), "watches/fitbit");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var TypedTimeSeriesEntryArray<HeartRateMeasure> $timeseries */
                $timeseries = $session->typedTimeSeriesFor(HeartRateMeasure::class, $id)
                    ->get();

                $this->assertCount(1, $timeseries);
                $this->assertEquals(59, $timeseries[0]->getValue()->getHeartRate());

                /** @var TypedTimeSeriesEntryArray<HeartRateMeasure> $timeseries */
                $timeseries2 = $session->typedTimeSeriesFor(HeartRateMeasure::class, $id)
                    ->get();

                $this->assertCount(1, $timeseries2);
                $this->assertEquals(59, $timeseries2[0]->getValue()->getHeartRate());

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testIncludeTimeSeriesAndMergeWithExistingRangesInCache_Typed(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $documentId = "users/ayende";

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf = $session->typedTimeSeriesFor(HeartRateMeasure::class, $documentId);

                for ($i = 0; $i < 360; $i++) {
                    $typedMeasure = HeartRateMeasure::create(6);
                    $tsf->append(DateUtils::addSeconds($baseLine, $i * 10), $typedMeasure, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->typedTimeSeriesFor(HeartRateMeasure::class, $documentId)
                        ->get(DateUtils::addMinutes($baseLine, 2), DateUtils::addMinutes($baseLine, 10));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(49, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $vals[48]->getTimestamp());

                $user = $session->load(
                    get_class($user),
                    $documentId,
                    function($i) use ($baseLine) { return $i->includeTimeSeries("heartRateMeasures", DateUtils::addMinutes($baseLine, 40), DateUtils::addMinutes($baseLine, 50)); }
                );

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                // should not go to server
                $vals = $session->typedTimeSeriesFor(HeartRateMeasure::class, $documentId)
                        ->get(DateUtils::addMinutes($baseLine, 40), DateUtils::addMinutes($baseLine, 50));

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $this->assertCount(61, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $vals[60]->getTimestamp());

                /** @var InMemoryDocumentSessionOperations $sessionOperations */
                $sessionOperations = $session;

                $cache = $sessionOperations->getTimeSeriesByDocId()[$documentId];
                $this->assertNotNull($cache);

                $ranges = $cache["heartRateMeasures"];
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());

                // we intentionally evict just the document (without it's TS data),
                // so that Load request will go to server

                $sessionOperations->documentsByEntity->evict($user);
                $sessionOperations->documentsById->remove($documentId);

                // should go to server to get [0, 2] and merge it into existing [2, 10]
                $user = $session->load(get_class($user),
                        $documentId,
                        function($i) use ($baseLine) { $i->includeTimeSeries("heartRateMeasures", $baseLine, DateUtils::addMinutes($baseLine, 2)); } );

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->typedTimeSeriesFor(HeartRateMeasure::class, $documentId)
                        ->get($baseLine, DateUtils::addMinutes($baseLine, 2));

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $this->assertCount(13, $vals);

                $this->assertEquals($baseLine, $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[12]->getTimestamp());

                $this->assertCount(2, $ranges);

                $this->assertEquals($baseLine, $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());

                // evict just the document

                $sessionOperations->documentsByEntity->evict($user);
                $sessionOperations->documentsById->remove($documentId);

                // should go to server to get [10, 16] and merge it into existing [0, 10]
                $user = $session->load(get_class($user),
                        $documentId,
                        function($i) use ($baseLine) { $i->includeTimeSeries("heartRateMeasures", DateUtils::addMinutes($baseLine, 10), DateUtils::addMinutes($baseLine, 16)); });

                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->typedTimeSeriesFor(HeartRateMeasure::class, $documentId)
                        ->get(DateUtils::addMinutes($baseLine, 10), DateUtils::addMinutes($baseLine, 16));

                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                $this->assertCount(37, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 16), $vals[36]->getTimestamp());

                $ranges = $cache["heartRateMeasures"];
                $this->assertCount(2, $ranges);

                $this->assertEquals($baseLine, $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 16), $ranges[0]->getTo());

                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());

                // evict just the document
                $sessionOperations->documentsByEntity->evict($user);
                $sessionOperations->documentsById->remove($documentId);

                // should go to server to get range [17, 19]
                // and add it to cache in between [10, 16] and [40, 50]

                $user = $session->load(get_class($user),
                        $documentId,
                        function($i) use ($baseLine) { $i->includeTimeSeries("heartRateMeasures", DateUtils::addMinutes($baseLine, 17), DateUtils::addMinutes($baseLine, 19)); } );

                $this->assertEquals(5, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->typedTimeSeriesFor(HeartRateMeasure::class, $documentId)
                        ->get(DateUtils::addMinutes($baseLine, 17), DateUtils::addMinutes($baseLine, 19));

                $this->assertEquals(5, $session->advanced()->getNumberOfRequests());

                $this->assertCount(13, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 17), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 19), $vals[12]->getTimestamp());

                $ranges = $cache["heartRateMeasures"];
                $this->assertCount(3, $ranges);


                $this->assertEquals($baseLine, $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 16), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 17), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 19), $ranges[1]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[2]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[2]->getTo());

                // evict just the document
                $sessionOperations->documentsByEntity->evict($user);
                $sessionOperations->documentsById->remove($documentId);

                // should go to server to get range [19, 40]
                // and merge the result with existing ranges [17, 19] and [40, 50]
                // into single range [17, 50]

                $user = $session->load(get_class($user),
                        $documentId,
                        function($i) use ($baseLine) { $i->includeTimeSeries("heartRateMeasures", DateUtils::addMinutes($baseLine, 18), DateUtils::addMinutes($baseLine, 48)); });

                $this->assertEquals(6, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->typedTimeSeriesFor(HeartRateMeasure::class, $documentId)
                        ->get(DateUtils::addMinutes($baseLine, 18), DateUtils::addMinutes($baseLine, 48));

                $this->assertEquals(6, $session->advanced()->getNumberOfRequests());

                $this->assertCount(181, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 18), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 48), $vals[180]->getTimestamp());

                $ranges = $cache["heartRateMeasures"];
                $this->assertCount(2, $ranges);

                $this->assertEquals($baseLine, $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 16), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 17), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());

                // evict just the document
                $sessionOperations->documentsByEntity->evict($user);
                $sessionOperations->documentsById->remove($documentId);

                // should go to server to get range [12, 22]
                // and merge the result with existing ranges [0, 16] and [17, 50]
                // into single range [0, 50]

                $user = $session->load(get_class($user),
                        $documentId,
                        function($i) use ($baseLine) { $i->includeTimeSeries("heartRateMeasures", DateUtils::addMinutes($baseLine, 12), DateUtils::addMinutes($baseLine, 22)); });

                $this->assertEquals(7, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->typedTimeSeriesFor(HeartRateMeasure::class, $documentId)
                        ->get(DateUtils::addMinutes($baseLine, 12), DateUtils::addMinutes($baseLine, 22));

                $this->assertEquals(7, $session->advanced()->getNumberOfRequests());

                $this->assertCount(61, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 12), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 22), $vals[60]->getTimestamp());

                $ranges = $cache["heartRateMeasures"];
                $this->assertCount(1, $ranges);

                $this->assertEquals($baseLine, $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[0]->getTo());

                // evict just the document
                $sessionOperations->documentsByEntity->evict($user);
                $sessionOperations->documentsById->remove($documentId);

                // should go to server to get range [50, ∞]
                // and merge the result with existing range [0, 50] into single range [0, ∞]

                $user = $session->load(get_class($user),
                        $documentId,
                        function($i) { $i->includeTimeSeriesByRange("heartRateMeasures", TimeSeriesRangeType::last(), TimeValue::ofMinutes(10));});

                $this->assertEquals(8, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->typedTimeSeriesFor(HeartRateMeasure::class, $documentId)
                        ->get(DateUtils::addMinutes($baseLine, 50), null);

                $this->assertEquals(8, $session->advanced()->getNumberOfRequests());

                $this->assertCount(60, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addSeconds(DateUtils::addMinutes($baseLine, 59), 50), $vals[59]->getTimestamp());

                $ranges = $cache["heartRateMeasures"];
                $this->assertCount(1, $ranges);

                $this->assertEquals($baseLine, $ranges[0]->getFrom());
                $this->assertNull($ranges[0]->getTo());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testIncludeTimeSeriesAndUpdateExistingRangeInCache_Typed(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf = $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende");

                for ($i = 0; $i < 360; $i++) {
                    $tsf->append(DateUtils::addSeconds($baseLine, $i * 10), HeartRateMeasure::create(6), "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var TypedTimeSeriesEntryArray<HeartRateMeasure> $vals */
                $vals = $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende")
                    ->get(DateUtils::addMinutes($baseLine, 2), DateUtils::addMinutes($baseLine, 10));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(49, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[0]->getTimestamp());

                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $vals[48]->getTimestamp());

                $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende")
                    ->append(DateUtils::addSeconds(DateUtils::addMinutes($baseLine, 3), 3),
                        HeartRateMeasure::create(6),
                        "watches/fitbit");
                $session->saveChanges();

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $user = $session->load(get_class($user),
                    "users/ayende",
                    function ($i) use ($baseLine) {
                        $i->includeTimeSeries("heartRateMeasures", DateUtils::addMinutes($baseLine, 3), DateUtils::addMinutes($baseLine, 5));
                    }
                );

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                // should not go to server
                $vals = $session->typedTimeSeriesFor(HeartRateMeasure::class, "users/ayende")
                    ->get(DateUtils::addMinutes($baseLine, 3), DateUtils::addMinutes($baseLine, 5));

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $this->assertCount(14, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $vals[0]->getTimestamp());

                $dt = DateUtils::addMinutes($baseLine, 3);
                $this->assertEquals(DateUtils::addSeconds($dt, 3), $vals[1]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $vals[13]->getTimestamp());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanServeTimeSeriesFromCache_Rollup(): void
    {
        $store = $this->getDocumentStore();
        try {
            $raw = new RawTimeSeriesPolicy(TimeValue::ofHours(24));

            $p1 = new TimeSeriesPolicy("By6Hours", TimeValue::ofHours(6), TimeValue::ofDays(4));
            $p2 = new TimeSeriesPolicy("By1Day", TimeValue::ofDays(1), TimeValue::ofDays(5));
            $p3 = new TimeSeriesPolicy("By30Minutes", TimeValue::ofMinutes(30), TimeValue::ofDays(2));
            $p4 = new TimeSeriesPolicy("By1Hour", TimeValue::ofMinutes(60), TimeValue::ofDays(3));

            $timeSeriesCollectionConfiguration = new TimeSeriesCollectionConfiguration();
            $timeSeriesCollectionConfiguration->setRawPolicy($raw);
            $timeSeriesCollectionConfiguration->setPolicies([ $p1, $p2, $p3, $p4 ]);

            $config = new TimeSeriesConfiguration();
            $config->setCollections(["users" => $timeSeriesCollectionConfiguration]);
            $config->setPolicyCheckFrequency(Duration::ofSeconds(1));

            $store->maintenance()->send(new ConfigureTimeSeriesOperation($config));
            $store->timeSeries()->register(User::class, StockPrice::class);

            $total = TimeValue::ofDays(12)->getValue();
            $baseLine = DateUtils::addDays(DateUtils::truncateDayOfMonth(new DateTime()), -12);

            $session = $store->openSession();
            try  {
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
                    $ts->append(DateUtils::addMinutes($baseLine, $i), $entry, "watches/fitbit");
                }
                $session->saveChanges();
            } finally {
                $session->close();
            }

            usleep(3200000); // wait for rollups

            $session = $store->openSession();
            try  {
                $ts = $session->timeSeriesRollupFor(StockPrice::class, "users/karmel", $p1->getName());
                $res = $ts->get();

                $this->assertCount(16, $res);

                // should not go to server
                $res = $ts->get($baseLine, DateUtils::addYears($baseLine, 1));
                $this->assertCount(16, $res);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanIncludeTypedTimeSeries_Rollup(): void
    {
        $store = $this->getDocumentStore();
        try {
            $raw = new RawTimeSeriesPolicy(TimeValue::ofHours(24));

            $p1 = new TimeSeriesPolicy("By6Hours", TimeValue::ofHours(6), TimeValue::ofDays(4));
            $p2 = new TimeSeriesPolicy("By1Day", TimeValue::ofDays(1), TimeValue::ofDays(5));
            $p3 = new TimeSeriesPolicy("By30Minutes", TimeValue::ofMinutes(30), TimeValue::ofDays(2));
            $p4 = new TimeSeriesPolicy("By1Hour", TimeValue::ofMinutes(60), TimeValue::ofDays(3));

            $timeSeriesCollectionConfiguration = new TimeSeriesCollectionConfiguration();
            $timeSeriesCollectionConfiguration->setRawPolicy($raw);
            $timeSeriesCollectionConfiguration->setPolicies([$p1, $p2, $p3, $p4]);

            $config = new TimeSeriesConfiguration();
            $config->setCollections(TimeSeriesCollectionConfigurationMap::fromArray(["users" => $timeSeriesCollectionConfiguration]));
            $config->setPolicyCheckFrequency(Duration::ofSeconds(1));

            $store->maintenance()->send(new ConfigureTimeSeriesOperation($config));
            $store->timeSeries()->register(User::class, StockPrice::class);

            $total = TimeValue::ofDays(12)->getValue();
            /** @var DateTime $baseLine */
            $baseLine = DateUtils::addDays(DateUtils::truncateDayOfMonth(new DateTime()), -12);

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
                    $ts->append(DateUtils::addMinutes($baseLine, $i), $entry, "watches/fitbit");
                }
                $session->saveChanges();
            } finally {
                $session->close();
            }

            usleep(3200000); // wait for rollups 1.2 second

            $session = $store->openSession();
            try {
                $user = $session->query(get_class($user))
                        ->include(function($i) use ($p1) { $i->includeTimeSeries("stockPrices@" . $p1->getName());})
                        ->first();

                // should not go to server
                /** @var TypedTimeSeriesRollupEntryArray <StockPrice>  $res */
                $res = $session->timeSeriesRollupFor(StockPrice::class, $user->getId(), $p1->getName())
                        ->get();

                $this->assertCount(16, $res);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
