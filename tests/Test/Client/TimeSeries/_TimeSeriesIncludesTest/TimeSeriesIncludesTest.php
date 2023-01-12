<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesIncludesTest;

use DateTime;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeResultList;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeType;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Primitives\TimeValue;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\Order;
use tests\RavenDB\RavenTestHelper;
use tests\RavenDB\RemoteTestBase;

class TimeSeriesIncludesTest extends RemoteTestBase
{
    public function testSessionLoadWithIncludeTimeSeries(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1-A");

                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");

                $tsf = $session->timeSeriesFor("orders/1-A", "Heartrate");
                $tsf->append($baseLine, 67, "watches/apple");
                $tsf->append(DateUtils::addMinutes($baseLine, 5), 64, "watches/apple");
                $tsf->append(DateUtils::addMinutes($baseLine, 10), 65, "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $order = $session->load(get_class($order), "orders/1-A",
                        function($i) { $i->includeDocuments("company")
                                ->includeTimeSeries("Heartrate"); });

                $company = $session->load(get_class($company), $order->getCompany());
                $this->assertEquals("HR", $company->getName());

                // should not go to server
                $values = $session->timeSeriesFor($order, "Heartrate")
                        ->get(null, null);

                $this->assertCount(3, $values);

                $this->assertCount(1, $values[0]->getValues());
                $this->assertEquals(67, $values[0]->getValues()[0]);
                $this->assertEquals("watches/apple", $values[0]->getTag());
                $this->assertEquals($baseLine, $values[0]->getTimestamp());

                $this->assertCount(1, $values[1]->getValues());
                $this->assertEquals(64, $values[1]->getValues()[0]);
                $this->assertEquals("watches/apple", $values[1]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $values[1]->getTimestamp());

                $this->assertCount(1, $values[2]->getValues());
                $this->assertEquals(65, $values[2]->getValues()[0]);
                $this->assertEquals("watches/fitbit", $values[2]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $values[2]->getTimestamp());

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testIncludeTimeSeriesAndMergeWithExistingRangesInCache(): void
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
                $tsf = $session->timeSeriesFor($documentId, "Heartrate");
                for ($i = 0; $i < 360; $i++) {
                    $tsf->append(DateUtils::addSeconds($baseLine, $i * 10), 6, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor($documentId, "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 2), DateUtils::addMinutes($baseLine, 10));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(49, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $vals[48]->getTimestamp());

                $user = $session
                    ->load(
                        get_class($user),
                        $documentId,
                        function($i) use ($baseLine) {
                            $i->includeTimeSeries("Heartrate",
                                DateUtils::addMinutes($baseLine, 40),
                                DateUtils::addMinutes($baseLine, 50));
                        }
                );

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->timeSeriesFor($documentId, "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 40), DateUtils::addMinutes($baseLine, 50));

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $this->assertCount(61, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $vals[60]->getTimestamp());

                /** @var InMemoryDocumentSessionOperations $sessionOperations */
                $sessionOperations = $session;

                $cache = $sessionOperations->getTimeSeriesByDocId()[$documentId];
                $this->assertNotNull($cache);

                /** @var TimeSeriesRangeResultList $ranges */
                $ranges = $cache["Heartrate"];
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
                $user = $session->load(get_class($user), $documentId,
                        function($i) use ($baseLine) { $i->includeTimeSeries("Heartrate", $baseLine, DateUtils::addMinutes($baseLine, 2)); });


                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->timeSeriesFor($documentId, "Heartrate")
                        ->get($baseLine, DateUtils::addMinutes($baseLine, 2));

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $this->assertCount(13, $vals);
                $this->assertEquals($baseLine, $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[12]->getTimestamp());

                /** @var TimeSeriesRangeResultList $ranges */
                $ranges = $cache["Heartrate"];
                $this->assertCount(2, $ranges);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 0), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());

                // evict just the document
                $sessionOperations->documentsByEntity->evict($user);
                $sessionOperations->documentsById->remove($documentId);

                // should go to server to get [10, 16] and merge it into existing [0, 10]
                $user = $session->load(get_class($user), $documentId,
                        function($i) use ($baseLine) { $i->includeTimeSeries("Heartrate", DateUtils::addMinutes($baseLine, 10), DateUtils::addMinutes($baseLine, 16)); } );

                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                // should not go to server
                $vals = $session->timeSeriesFor($documentId, "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 10), DateUtils::addMinutes($baseLine, 16));

                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                $this->assertCount(37, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 16), $vals[36]->getTimestamp());

                /** @var TimeSeriesRangeResultList $ranges */
                $ranges = $cache["Heartrate"];
                $this->assertCount(2, $ranges);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 0), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 16), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());

                // evict just the document
                $sessionOperations->documentsByEntity->evict($user);
                $sessionOperations->documentsById->remove($documentId);

                // should go to server to get range [17, 19]
                // and add it to cache in between [10, 16] and [40, 50]

                $user = $session->load(get_class($user), $documentId,
                        function($i) use ($baseLine) { $i->includeTimeSeries("Heartrate",
                                DateUtils::addMinutes($baseLine, 17), DateUtils::addMinutes($baseLine, 19)); });

                $this->assertEquals(5, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->timeSeriesFor($documentId, "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 17), DateUtils::addMinutes($baseLine, 19));

                $this->assertEquals(5, $session->advanced()->getNumberOfRequests());

                $this->assertCount(13, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 17), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 19), $vals[12]->getTimestamp());

                /** @var TimeSeriesRangeResultList $ranges */
                $ranges = $cache["Heartrate"];
                $this->assertCount(3, $ranges);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 0), $ranges[0]->getFrom());
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

                $user = $session->load(get_class($user), $documentId,
                        function($i) use ($baseLine) { $i->includeTimeSeries("Heartrate",
                                DateUtils::addMinutes($baseLine, 18), DateUtils::addMinutes($baseLine, 48));});

                $this->assertEquals(6, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->timeSeriesFor($documentId, "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 18), DateUtils::addMinutes($baseLine, 48));

                $this->assertEquals(6, $session->advanced()->getNumberOfRequests());

                $this->assertCount(181, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 18), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 48), $vals[180]->getTimestamp());

                /** @var TimeSeriesRangeResultList $ranges */
                $ranges = $cache["Heartrate"];
                $this->assertCount(2, $ranges);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 0), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 16), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 17), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());

                // evict just the document
                $sessionOperations->documentsByEntity->evict($user);
                $sessionOperations->documentsById->remove($documentId);

                // should go to server to get range [12, 22]
                // and merge the result with existing ranges [0, 16] and [17, 50]
                // into single range [0, 50]

                $user = $session->load(get_class($user), $documentId,
                        function($i) use ($baseLine) { $i->includeTimeSeries("Heartrate", DateUtils::addMinutes($baseLine, 12), DateUtils::addMinutes($baseLine, 22)); } );

                $this->assertEquals(7, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->timeSeriesFor($documentId, "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 12), DateUtils::addMinutes($baseLine, 22));

                $this->assertEquals(7, $session->advanced()->getNumberOfRequests());

                $this->assertCount(61, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 12), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 22), $vals[60]->getTimestamp());

                /** @var TimeSeriesRangeResultList $ranges */
                $ranges = $cache["Heartrate"];
                $this->assertCount(1, $ranges);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 0), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[0]->getTo());

                // evict just the document
                $sessionOperations->documentsByEntity->evict($user);
                $sessionOperations->documentsById->remove($documentId);

                // should go to server to get range [50, ∞]
                // and merge the result with existing range [0, 50] into single range [0, ∞]

                $user = $session->load(get_class($user), $documentId,
                        function($i) { $i->includeTimeSeriesRangeType("heartrate", TimeSeriesRangeType::last(), TimeValue::ofMinutes(10));});

                $this->assertEquals(8, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->timeSeriesFor($documentId, "heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 50), null);

                $this->assertEquals(8, $session->advanced()->getNumberOfRequests());

                $this->assertCount(60, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addSeconds(DateUtils::addMinutes($baseLine, 59), 50), $vals[59]->getTimestamp());

                /** @var TimeSeriesRangeResultList $ranges */
                $ranges = $cache["Heartrate"];
                $this->assertCount(1, $ranges);
                $this->assertEquals($baseLine, $ranges[0]->getFrom());
                $this->assertEquals(null, $ranges[0]->getTo());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testIncludeTimeSeriesAndUpdateExistingRangeInCache(): void
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
                $tsf = $session->timeSeriesFor($documentId, "Heartrate");
                for ($i = 0; $i < 360; $i++) {
                    $tsf->append(DateUtils::addSeconds($baseLine, $i * 10), 6, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 2), DateUtils::addMinutes($baseLine, 10));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(49, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $vals[48]->getTimestamp());

                $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->append(DateUtils::addSeconds(DateUtils::addMinutes($baseLine, 3), 3), 6, "watches/fitbit");
                $session->saveChanges();

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $user = $session->load(get_class($user), "users/ayende",
                        function($i) use ($baseLine) { $i->includeTimeSeries("Heartrate",
                                DateUtils::addMinutes($baseLine, 3), DateUtils::addMinutes($baseLine, 5));});

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 3), DateUtils::addMinutes($baseLine, 5));

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $this->assertCount(14, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addSeconds(DateUtils::addMinutes($baseLine, 3), 3), $vals[1]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $vals[13]->getTimestamp());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testIncludeMultipleTimeSeries(): void
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
                for ($i = 0; $i < 360; $i++) {
                    $session->timeSeriesFor("users/ayende", "Heartrate")
                            ->append(DateUtils::addSeconds($baseLine, $i * 10), 6, "watches/fitbit");
                    $session->timeSeriesFor("users/ayende", "BloodPressure")
                            ->append(DateUtils::addSeconds($baseLine, $i * 10), 66, "watches/fitbit");
                    $session->timeSeriesFor("users/ayende", "Nasdaq")
                            ->append(DateUtils::addSeconds($baseLine, $i * 10), 8097.23, "nasdaq.com");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(get_class($user), "users/ayende",
                        function($i) use ($baseLine) { $i
                                ->includeTimeSeries("Heartrate", DateUtils::addMinutes($baseLine, 3), DateUtils::addMinutes($baseLine, 5))
                                ->includeTimeSeries("BloodPressure", DateUtils::addMinutes($baseLine, 40), DateUtils::addMinutes($baseLine, 45))
                                ->includeTimeSeries("Nasdaq", DateUtils::addMinutes($baseLine, 15), DateUtils::addMinutes($baseLine, 25));});

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertEquals("Oren", $user->getName());

                // should not go to server

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 3), DateUtils::addMinutes($baseLine, 5));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(13, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $vals[12]->getTimestamp());

                // should not go to server

                $vals = $session->timeSeriesFor("users/ayende", "BloodPressure")
                        ->get(DateUtils::addMinutes($baseLine, 42), DateUtils::addMinutes($baseLine, 43));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(7, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 42), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 43), $vals[6]->getTimestamp());

                // should not go to server

                $vals = $session->timeSeriesFor("users/ayende", "BloodPressure")
                        ->get(DateUtils::addMinutes($baseLine, 40), DateUtils::addMinutes($baseLine, 45));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(31, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 45), $vals[30]->getTimestamp());

                // should not go to server

                $vals = $session->timeSeriesFor("users/ayende", "Nasdaq")
                        ->get(DateUtils::addMinutes($baseLine, 15), DateUtils::addMinutes($baseLine, 25));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(61, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 15), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 25), $vals[60]->getTimestamp());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testShouldCacheEmptyTimeSeriesRanges(): void
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
                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                for ($i = 0; $i < 360; $i++) {
                    $tsf->append(DateUtils::addSeconds($baseLine, $i * 10), 6, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(get_class($user), "users/ayende",
                        function($i) use ($baseLine) { $i->includeTimeSeries("Heartrate", DateUtils::addMinutes($baseLine, -30), DateUtils::addMinutes($baseLine, -10));} );

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertEquals("Oren", $user->getName());

                // should not go to server

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, -30), DateUtils::addMinutes($baseLine, -10));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertEmpty($vals);

                /** @var InMemoryDocumentSessionOperations $sessionOperations */
                $sessionOperations = $session;
                $cache = $sessionOperations->getTimeSeriesByDocId()["users/ayende"];
                /** @var TimeSeriesRangeResultList $ranges */
                $ranges = $cache["Heartrate"];
                $this->assertCount(1, $ranges);

                $this->assertEmpty($ranges[0]->getEntries());

                $this->assertEquals(DateUtils::addMinutes($baseLine, -30), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, -10), $ranges[0]->getTo());

                // should not go to server

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, -25), DateUtils::addMinutes($baseLine, -15));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertEmpty($vals);

                $session->advanced()->evict($user);

                $user = $session->load(get_class($user), "users/ayende",
                        function($i) use ($baseLine) { $i->includeTimeSeries("BloodPressure",
                                DateUtils::addMinutes($baseLine, 10), DateUtils::addMinutes($baseLine, 30));});

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $vals = $session->timeSeriesFor("users/ayende", "BloodPressure")
                        ->get(DateUtils::addMinutes($baseLine, 10), DateUtils::addMinutes($baseLine, 30));

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $this->assertEmpty($vals);

                /** @var InMemoryDocumentSessionOperations $sessionOperations */
                $sessionOperations = $session;

                $cache = $sessionOperations->getTimeSeriesByDocId()["users/ayende"];
                $ranges = $cache["BloodPRessure"];
                $this->assertCount(1, $ranges);
                $this->assertEmpty($ranges[0]->getEntries());

                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 30), $ranges[0]->getTo());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testMultiLoadWithIncludeTimeSeries(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Oren");
                $session->store($user1, "users/ayende");

                $user2 = new User();
                $user2->setName("Pawel");
                $session->store($user2, "users/ppekrol");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf1 = $session->timeSeriesFor("users/ayende", "Heartrate");
                $tsf2 = $session->timeSeriesFor("users/ppekrol", "Heartrate");

                for ($i = 0; $i < 360; $i++) {
                    $tsf1->append(DateUtils::addSeconds($baseLine, $i * 10), 6, "watches/fitbit");

                    if ($i % 2 == 0) {
                        $tsf2->append(DateUtils::addSeconds($baseLine, $i * 10), 7, "watches/fitbit");
                    }
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $users = $session->load(User::class, ["users/ayende", "users/ppekrol"],
                        function($i) use ($baseLine) { $i->includeTimeSeries("Heartrate", $baseLine, DateUtils::addMinutes($baseLine, 30));});

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertEquals("Oren", $users["users/ayende"]->getName());
                $this->assertEquals("Pawel", $users["users/ppekrol"]->getName());

                // should not go to server

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get($baseLine, DateUtils::addMinutes($baseLine, 30));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(181, $vals);

                $this->assertEquals($baseLine, $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 30), $vals[180]->getTimestamp());

                // should not go to server

                $vals = $session->timeSeriesFor("users/ppekrol", "Heartrate")
                        ->get($baseLine, DateUtils::addMinutes($baseLine, 30));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(91, $vals);

                $this->assertEquals($baseLine, $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 30), $vals[90]->getTimestamp());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function includeTimeSeriesAndDocumentsAndCounters(): void
    {
        $store = $this->getDocumentStore();
        try {
//            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());
//
            $session = $store->openSession();
            try {
//                $user = new User();
//                $user->setName("Oren");
//                $user->setWorksAt("companies/1");
//                $session->store($user, "users/ayende");
//
//                $company = new Company();
//                $company->setName("HR");
//                $session->store($company, "companies/1");
//
//                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
//                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
//
//                for ($i = 0; $i < 360; $i++) {
//                    $tsf->append(DateUtils::addSeconds($baseLine, $i * 10), 67, "watches/fitbit");
//                }
//
//                $session->countersFor("users/ayende").increment("likes", 100);
//                $session->countersFor("users/ayende").increment("dislikes", 5);
//
//                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
//                $user = $session->load(get_class($user), "users/ayende",
//                        i -> i.includeDocuments("worksAt")
//                                ->includeTimeSeries("Heartrate", $baseLine, DateUtils::addMinutes($baseLine, 30))
//                                .includeCounter("likes")
//                                .includeCounter("dislikes"));
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                assertThat($user->getName())
//                        .isEqualTo("Oren");
//
//                // should not go to server
//
//                $company = $session->load(get_class($company), $user->getWorksAt());
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                assertThat($company->getName())
//                        .isEqualTo("HR");
//
//                // should not go to server
//                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
//                        ->get($baseLine, DateUtils::addMinutes($baseLine, 30)));
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                $this->assertCount(13, $vals);
//                        .hasSize(181);
//
//                assertThat($vals[0]->getTimestamp())
//                        .isEqualTo($baseLine);
//                assertThat($vals[0].getTag())
//                        .isEqualTo("watches/fitbit");
//                assertThat($vals[0].getValues()[0])
//                        .isEqualTo(67);
//                assertThat($vals[180)->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseLine, 30));
//
//                // should not go to server
//
//                Map<String, Long> counters = $session->countersFor("users/ayende")
//                        .getAll();
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                Long counter = counters.get("likes");
//                assertThat(counter)
//                        .isEqualTo(100);
//                counter = counters.get("dislikes");
//                assertThat(counter)
//                        .isEqualTo(5);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWithIncludeTimeSeries(): void
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
                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");

                for ($i = 0; $i < 360; $i++) {
                    $tsf->append(DateUtils::addSeconds($baseLine, $i * 10), 67, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->query(get_class($user))
                        ->include(function($i) { $i->includeTimeSeries("Heartrate");});

                $result = $query->toList();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertEquals("Oren", $result[0]->getName());

                // should not go to server

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get($baseLine, DateUtils::addMinutes($baseLine, 30));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(181, $vals);

                $this->assertEquals($baseLine, $vals[0]->getTimestamp());
                $this->assertEquals("watches/fitbit", $vals[0]->getTag());
                $this->assertEquals(67, $vals[0]->getValues()[0]);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 30), $vals[180]->getTimestamp());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanLoadAsyncWithIncludeTimeSeries_LastRange_ByCount(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::addHours(RavenTestHelper::utcToday(), 12);

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1-A");

                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");
                $tsf = $session->timeSeriesFor("orders/1-A", "heartrate");

                for ($i = 0; $i < 15; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, -$i), $i, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $order = $session->load(get_class($order), "orders/1-A",
                        function($i) { $i->includeDocuments("company")
                                ->includeTimeSeriesRangeType("heartrate", TimeSeriesRangeType::last(), 11);});

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // should not go to server

                $company = $session->load(get_class($company), $order->getCompany());

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertEquals("HR", $company->getName());

                // should not go to server
                $values = $session->timeSeriesFor($order, "heartrate")
                        ->get(DateUtils::addMinutes($baseLine, -10), null);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(11, $values);

                for ($i = 0; $i < count($values); $i++) {
                    $this->assertCount(1, $values[$i]->getValues());
                    $this->assertEquals(count($values) - 1 - $i, $values[$i]->getValues()[0]);
                    $this->assertEquals("watches/fitbit", $values[$i]->getTag());
                    $this->assertEquals(DateUtils::addMinutes($baseLine, -(count($values) - 1 - $i)), $values[$i]->getTimestamp());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestCanLoadAsyncWithInclude_AllTimeSeries_LastRange_ByTime(): void
    {
        $store = $this->getDocumentStore();
        try {
//            Date $baseLine = new Date();
//
            $session = $store->openSession();
            try {
//                $company = new Company();
//                $company->setName("HR");
//                $session->store($company, "companies/1-A");
//
//                $order = new Order();
//                $order->setCompany("companies/1-A");
//                $session->store($order, "orders/1-A");
//
//                $tsf = $session->timeSeriesFor("orders/1-A", "heartrate");
//
//                for ($i = 0; $i < 15; $i++) {
//                    $tsf->append(DateUtils::addMinutes($baseLine, -i), $i, "watches/bitfit");
//                }
//
//                $tsf2 = $session->timeSeriesFor("orders/1-A", "speedrate");
//                for ($i = 0; $i < 15; $i++) {
//                    tsf2->append(DateUtils::addMinutes($baseLine, -i), $i, "watches/fitbit");
//                }
//
//                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
//                $order = $session->load(get_class($order), "orders/1-A",
//                        function($i) use ($baseLine) { $i->includeDocuments("company").includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.ofMinutes(10)));
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                // should not go to server
//                $company = $session->load(get_class($company), $order->getCompany());
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                assertThat($company->getName())
//                        .isEqualTo("HR");
//
//                // should not go to server
//                TimeSeriesEntry[] heartrateValues = $session->timeSeriesFor($order, "heartrate")
//                        ->get(DateUtils::addMinutes($baseLine, -10), null);
//
//                assertThat(heartrateValues)
//                        .hasSize(11);
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                TimeSeriesEntry[] speedrateValues = $session->timeSeriesFor($order, "speedrate")
//                        ->get(DateUtils::addMinutes($baseLine, -10), null);
//
//                assertThat(speedrateValues)
//                        .hasSize(11);
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                for ($i = 0; $i < heartrateValues.length; $i++) {
//                    assertThat(heartrateValues[i].getValues())
//                            .hasSize(1);
//                    assertThat(heartrateValues[i].getValues()[0])
//                            .isEqualTo(heartrateValues.length - 1 - i);
//                    assertThat(heartrateValues[i].getTag())
//                            .isEqualTo("watches/bitfit");
//                    assertThat(heartrateValues[i]->getTimestamp())
//                            .isEqualTo(DateUtils::addMinutes($baseLine, -(heartrateValues.length - 1 - i)));
//                }
//
//                for ($i = 0; $i < speedrateValues.length; $i++) {
//                    assertThat(speedrateValues[i].getValues())
//                            .hasSize(1);
//                    assertThat(speedrateValues[i].getValues()[0])
//                            .isEqualTo(speedrateValues.length - 1 - i);
//                    assertThat(speedrateValues[i].getTag())
//                            .isEqualTo("watches/fitbit");
//                    assertThat(speedrateValues[i]->getTimestamp())
//                            .isEqualTo(DateUtils::addMinutes($baseLine, -(speedrateValues.length - 1 - i)));
//                }
            } finally {
                $session->close();
            }

        } finally {
            $store->close();
        }
    }

    public function atestCanLoadAsyncWithInclude_AllTimeSeries_LastRange_ByCount(): void
    {
        $store = $this->getDocumentStore();
        try {
//            Date $baseLine = DateUtils::addHours(RavenTestHelper.utcToday(), 3);
//
            $session = $store->openSession();
            try {
//                $company = new Company();
//                $company->setName("HR");
//                $session->store($company, "companies/1-A");
//
//                $order = new Order();
//                $order->setCompany("companies/1-A");
//                $session->store($order, "orders/1-A");
//                $tsf = $session->timeSeriesFor("orders/1-A", "Heartrate");
//                for ($i = 0; $i < 15; $i++) {
//                    $tsf->append(DateUtils::addMinutes($baseLine, -i), $i, "watches/fitbit");
//                }
//                $tsf2 = $session->timeSeriesFor("orders/1-A", "speedrate");
//                for ($i = 0; $i < 15; $i++) {
//                    tsf2->append(DateUtils::addMinutes($baseLine, -i), $i, "watches/fitbit");
//                }
//
//                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
//                $order = $session->load(get_class($order), "orders/1-A",
//                        function($i) use ($baseLine) { $i->includeDocuments("company").includeAllTimeSeries(TimeSeriesRangeType.LAST, 11));
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                // should not go to server
//                $company = $session->load(get_class($company), $order->getCompany());
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                assertThat($company->getName())
//                        .isEqualTo("HR");
//
//                // should not go to server
//                TimeSeriesEntry[] heartrateValues = $session->timeSeriesFor($order, "heartrate")
//                        ->get(DateUtils::addMinutes($baseLine, -10), null);
//
//                assertThat(heartrateValues)
//                        .hasSize(11);
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                TimeSeriesEntry[] speedrateValues = $session->timeSeriesFor($order, "speedrate")
//                        ->get(DateUtils::addMinutes($baseLine, -10), null);
//
//                assertThat(speedrateValues)
//                        .hasSize(11);
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                for ($i = 0; $i < heartrateValues.length; $i++) {
//                    assertThat(heartrateValues[i].getValues())
//                            .hasSize(1);
//                    assertThat(heartrateValues[i].getValues()[0])
//                            .isEqualTo(heartrateValues.length - 1 - i);
//                    assertThat(heartrateValues[i].getTag())
//                            .isEqualTo("watches/fitbit");
//                    assertThat(heartrateValues[i]->getTimestamp())
//                            .isEqualTo(DateUtils::addMinutes($baseLine, -(heartrateValues.length - 1 - i)));
//                }
//
//                for ($i = 0; $i < speedrateValues.length; $i++) {
//                    assertThat(speedrateValues[i].getValues())
//                            .hasSize(1);
//                    assertThat(speedrateValues[i].getValues()[0])
//                            .isEqualTo(speedrateValues.length - 1 - i);
//                    assertThat(speedrateValues[i].getTag())
//                            .isEqualTo("watches/fitbit");
//                    assertThat(speedrateValues[i]->getTimestamp())
//                            .isEqualTo(DateUtils::addMinutes($baseLine, -(speedrateValues.length - 1 - i)));
//                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function shouldThrowOnIncludeAllTimeSeriesAfterIncludingTimeSeries(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11)
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.ofMinutes(10)));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, $integer.MAX_VALUE)
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, $integer.MAX_VALUE)
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.ofMinutes(10)));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, $integer.MAX_VALUE)
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, 11)
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.ofMinutes(10)));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, 11)
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries'.");
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isZero();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function shouldThrowOnIncludingTimeSeriesAfterIncludeAllTimeSeries(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11)
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.ofMinutes(10)));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, $integer.MAX_VALUE)
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.ofMinutes(10))
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, TimeValue.MAX_VALUE));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeTimeSeries' or 'includeAllTimeSeries' after using 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11)
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, TimeValue.MAX_VALUE));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeTimeSeries' or 'includeAllTimeSeries' after using 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.ofMinutes(10))
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, 11));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeTimeSeries' or 'includeAllTimeSeries' after using 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11)
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, 11));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeTimeSeries' or 'includeAllTimeSeries' after using 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11)
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.ofMinutes(10)));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.MAX_VALUE)
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.ofMinutes(10))
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, TimeValue.MAX_VALUE));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeTimeSeries' or 'includeAllTimeSeries' after using 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11)
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, TimeValue.MAX_VALUE));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeTimeSeries' or 'includeAllTimeSeries' after using 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.ofMinutes(10))
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, 11));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeTimeSeries' or 'includeAllTimeSeries' after using 'includeAllTimeSeries'.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, 11)
//                                    ->includeTimeSeries("heartrate", TimeSeriesRangeType.LAST, 11));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("IIncludeBuilder : Cannot use 'includeTimeSeries' or 'includeAllTimeSeries' after using 'includeAllTimeSeries'.");
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isZero();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function shouldThrowOnIncludingTimeSeriesWithLastRangeZeroOrNegativeTime(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.MIN_VALUE));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("Time range type cannot be set to LAST when time is negative or zero.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, TimeValue.ZERO));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("Time range type cannot be set to LAST when time is negative or zero.");
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isZero();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function shouldThrowOnIncludingTimeSeriesWithNoneRange(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.NONE, TimeValue.ofMinutes(-30)));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("Time range type cannot be set to NONE when time is specified.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.NONE, TimeValue.ZERO));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("Time range type cannot be set to NONE when time is specified.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.NONE, 1024));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("Time range type cannot be set to NONE when count is specified.");
//
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.NONE, TimeValue.ofMinutes(30)));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("Time range type cannot be set to NONE when time is specified.");
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(0);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function shouldThrowOnIncludingTimeSeriesWithNegativeCount(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
//                assertThatThrownBy(() -> {
//                    $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    .includeAllTimeSeries(TimeSeriesRangeType.LAST, -1024));
//                })
//                        .isExactlyInstanceOf(IllegalArgumentException.class)
//                        .hasMessageStartingWith("Count have to be positive.");
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestCanLoadAsyncWithInclude_ArrayOfTimeSeriesLastRangeByTime(): void
    {
        $this->canLoadAsyncWithInclude_ArrayOfTimeSeriesLastRange(true);
    }

    public function atestCanLoadAsyncWithInclude_ArrayOfTimeSeriesLastRangeByCount(): void
    {
        $this->canLoadAsyncWithInclude_ArrayOfTimeSeriesLastRange(false);
    }

    private function canLoadAsyncWithInclude_ArrayOfTimeSeriesLastRange(bool $byTime): void
    {
        $store = $this->getDocumentStore();
        try {
//            Date $baseLine = byTime ? new Date() : RavenTestHelper.utcToday();
//
            $session = $store->openSession();
            try {
//                $company = new Company();
//                $company->setName("HR");
//                $session->store($company, "companies/1-A");
//
//                $order = new Order();
//                $order->setCompany("companies/1-A");
//                $session->store($order, "orders/1-A");
//
//                $tsf = $session->timeSeriesFor("orders/1-A", "heartrate");
//                $tsf->append($baseLine, 67, "watches/apple");
//                $tsf->append(DateUtils::addMinutes($baseLine, -5), 64, "watches/apple");
//                $tsf->append(DateUtils::addMinutes($baseLine, -10), 65, "watches/fitbit");
//
//                $tsf2 = $session->timeSeriesFor("orders/1-A", "speedrate");
//                tsf2->append(DateUtils::addMinutes($baseLine, -15), 6, "watches/bitfit");
//                tsf2->append(DateUtils::addMinutes($baseLine, -10), 7, "watches/bitfit");
//                tsf2->append(DateUtils::addMinutes($baseLine, -9), 7, "watches/bitfit");
//                tsf2->append(DateUtils::addMinutes($baseLine, -8), 6, "watches/bitfit");
//
//                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
//                $order = null;
//                if (byTime) {
//                    order = $session->load(get_class($order), "orders/1-A",
//                            i -> i
//                                    .includeDocuments("company")
//                                    ->includeTimeSeries(new String[] { "heartrate", "speedrate" }, TimeSeriesRangeType.LAST, TimeValue.ofMinutes(10))
//                    );
//                } else {
//                    order = $session->load(get_class($order), "orders/1-A",
//                            function($i) use ($baseLine) { $i->includeDocuments("company")->includeTimeSeries(new String[]{ "heartrate", "speedrate" }, TimeSeriesRangeType.LAST, 3));
//                }
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                // should not go to server
//                $company = $session->load(get_class($company), $order->getCompany());
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//                assertThat($company->getName())
//                        .isEqualTo("HR");
//
//                // should not go to server
//                TimeSeriesEntry[] heartrateValues = $session->timeSeriesFor($order, "heartrate")
//                        ->get(DateUtils::addMinutes($baseLine, -10), null);
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//                assertThat(heartrateValues)
//                        .hasSize(3);
//
//                assertThat(heartrateValues[0].getValues())
//                        .hasSize(1);
//                assertThat(heartrateValues[0].getValues()[0])
//                        .isEqualTo(65);
//                assertThat(heartrateValues[0].getTag())
//                        .isEqualTo("watches/fitbit");
//                assertThat(heartrateValues[0]->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseLine, -10));
//
//                assertThat(heartrateValues[1].getValues())
//                        .hasSize(1);
//                assertThat(heartrateValues[1].getValues()[0])
//                        .isEqualTo(64);
//                assertThat(heartrateValues[1].getTag())
//                        .isEqualTo("watches/apple");
//                assertThat(heartrateValues[1]->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseLine, -5));
//
//                assertThat(heartrateValues[2].getValues())
//                        .hasSize(1);
//                assertThat(heartrateValues[2].getValues()[0])
//                        .isEqualTo(67);
//                assertThat(heartrateValues[2].getTag())
//                        .isEqualTo("watches/apple");
//                assertThat(heartrateValues[2]->getTimestamp())
//                        .isEqualTo($baseLine);
//
//                // should not go to server
//                TimeSeriesEntry[] speedrateValues = $session->timeSeriesFor($order, "speedrate")
//                        ->get(DateUtils::addMinutes($baseLine, -10), null);
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//                assertThat(speedrateValues)
//                        .hasSize(3);
//
//                assertThat(speedrateValues[0].getValues())
//                        .hasSize(1);
//                assertThat(speedrateValues[0].getValues()[0])
//                        .isEqualTo(7);
//                assertThat(speedrateValues[0].getTag())
//                        .isEqualTo("watches/bitfit");
//                assertThat(speedrateValues[0]->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseLine, -10));
//
//                assertThat(speedrateValues[1].getValues())
//                        .hasSize(1);
//                assertThat(speedrateValues[1].getValues()[0])
//                        .isEqualTo(7);
//                assertThat(speedrateValues[1].getTag())
//                        .isEqualTo("watches/bitfit");
//                assertThat(speedrateValues[1]->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseLine, -9));
//
//                assertThat(speedrateValues[2].getValues())
//                        .hasSize(1);
//                assertThat(speedrateValues[2].getValues()[0])
//                        .isEqualTo(6);
//                assertThat(speedrateValues[2].getTag())
//                        .isEqualTo("watches/bitfit");
//                assertThat(speedrateValues[2]->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseLine, -8));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
