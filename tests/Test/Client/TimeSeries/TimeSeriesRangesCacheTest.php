<?php

namespace tests\RavenDB\Test\Client\TimeSeries;

use DateTime;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeResultList;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class TimeSeriesRangesCacheTest extends RemoteTestBase
{
    public function testShouldGetTimeSeriesValueFromCache(): void {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");
                $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->append(DateUtils::addMinutes($baseLine, 1), [ 59 ], "watches/fitbit");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get()[0];

                $this->assertEquals([ 59 ], $val->getValues());

                $this->assertEquals("watches/fitbit", $val->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $val->getTimestamp());

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // should load from cache
                $val = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get()[0];

                $this->assertEquals([ 59 ], $val->getValues());

                $this->assertEquals("watches/fitbit", $val->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $val->getTimestamp());

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testShouldGetPartialRangeFromCache(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");
                $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->append(DateUtils::addMinutes($baseLine, 1), [ 59 ], "watches/fitbit");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get()[0];

                $this->assertEquals([ 59 ], $val->getValues());

                $this->assertEquals("watches/fitbit", $val->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $val->getTimestamp());

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // should load from cache
                $val = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get($baseLine, DateUtils::addDays($baseLine, 1))[0];

                $this->assertEquals([ 59 ], $val->getValues());

                $this->assertEquals("watches/fitbit", $val->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $val->getTimestamp());

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                /** @var InMemoryDocumentSessionOperations $inMemoryDocumentSession */
                $inMemoryDocumentSession = $session;

                $this->assertArrayHasKey("users/ayende", $inMemoryDocumentSession->getTimeSeriesByDocId());

                $cache = $inMemoryDocumentSession->getTimeSeriesByDocId()["users/ayende"];
                $this->assertNotNull($cache);

                $this->assertArrayHasKey("Heartrate", $cache);
                /** @var TimeSeriesRangeResultList $ranges */
                $ranges = $cache["Heartrate"];
                $this->assertNotNull($ranges);
                $this->assertCount(1, $ranges);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testShouldGetPartialRangeFromCache2(): void
    {
        $start = 5;
        $pageSize = 10;

        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");

                $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->append(DateUtils::addMinutes($baseLine, 1), 59, "watches/fitbit");
                $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->append(DateUtils::addMinutes($baseLine, 2), 60, "watches/fitbit");
                $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->append(DateUtils::addMinutes($baseLine, 3), 61, "watches/fitbit");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addDays($baseLine, 2), DateUtils::addDays($baseLine, 3), null, $start, $pageSize);

                $this->assertEmpty($val);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $val = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addDays($baseLine, 1), DateUtils::addDays($baseLine, 4), null, $start, $pageSize);

                $this->assertEmpty($val);

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(null, null, null, $start, $pageSize);

                $this->assertEmpty($val);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $val = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addDays($baseLine, 1), DateUtils::addDays($baseLine, 4), null, $start, $pageSize);

                $this->assertEmpty($val);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testShouldMergeTimeSeriesRangesInCache(): void
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
                    $tsf->append(DateUtils::addSeconds($baseLine, $i * 10), [ 6 ], "watches/fitbit");
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

                // should load partial range from cache
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 5), DateUtils::addMinutes($baseLine, 7));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertCount(13, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 7), $vals[12]->getTimestamp());

                // should go to server

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 40), DateUtils::addMinutes($baseLine, 50));

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $this->assertCount(61,  $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $vals[60]->getTimestamp());

                /** @var InMemoryDocumentSessionOperations $s */
                $s = $session;

                $cache = $s->getTimeSeriesByDocId()["users/ayende"];
                $this->assertNotNull($cache);

                $ranges = $cache["Heartrate"];
                $this->assertNotNull($ranges);
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $ranges[0]->getTo());

                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());

                // should go to server to get [0, 2] and merge it into existing [2, 10]
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get($baseLine, DateUtils::addMinutes($baseLine, 5));

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $this->assertCount(31, $vals);

                $this->assertEquals($baseLine, $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $vals[30]->getTimestamp());

                $ranges = $cache["Heartrate"];
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 0), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $ranges[0]->getTo());

                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());

                // should go to server to get [10, 16] and merge it into existing [0, 10]
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 8), DateUtils::addMinutes($baseLine, 16));

                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                $this->assertCount(49, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 8), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 16), $vals[48]->getTimestamp());

                $ranges = $cache["Heartrate"];
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 0), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 16), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());

                // should go to server to get range [17, 19]
                // and add it to cache in between [10, 16] and [40, 50]

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 17), DateUtils::addMinutes($baseLine, 19));

                $this->assertEquals(5, $session->advanced()->getNumberOfRequests());

                $this->assertCount(13,  $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 17), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 19), $vals[12]->getTimestamp());

                $ranges = $cache["Heartrate"];
                $this->assertEquals(DateUtils::addMinutes($baseLine, 0), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 16), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 17), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 19), $ranges[1]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[2]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[2]->getTo());

                // should go to server to get range [19, 40]
                // and merge the result with existing ranges [17, 19] and [40, 50]
                // into single range [17, 50]

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 18), DateUtils::addMinutes($baseLine, 48));

                $this->assertEquals(6, $session->advanced()->getNumberOfRequests());

                $this->assertCount(181, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 18), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 48), $vals[180]->getTimestamp());

                $ranges = $cache["Heartrate"];
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 0), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 16), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 17), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());

                // should go to server to get range [16, 17]
                // and merge the result with existing ranges [0, 16] and [17, 50]
                // into single range [0, 50]

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 12), DateUtils::addMinutes($baseLine, 22));

                $this->assertEquals(7, $session->advanced()->getNumberOfRequests());

                $this->assertCount(61, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 12), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 22), $vals[60]->getTimestamp());

                $ranges = $cache["Heartrate"];
                $this->assertCount(1, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 0), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[0]->getTo());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testShouldMergeTimeSeriesRangesInCache2(): void
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
                    $tsf->append(DateUtils::addSeconds($baseLine, $i * 10), 60, "watches/fitbit");
                }

                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate2");

                $tsf->append(DateUtils::addHours($baseLine, 1), 70, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 90), 75, "watches/fitbit");

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

                // should go the server
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 22), DateUtils::addMinutes($baseLine, 32));

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $this->assertCount(61, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 22), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 32), $vals[60]->getTimestamp());

                // should go to server
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 1), DateUtils::addMinutes($baseLine, 11));

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $this->assertCount(61, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 11), $vals[60]->getTimestamp());

                /** @var InMemoryDocumentSessionOperations $s */
                $s = $session;
                $cache = $s->getTimeSeriesByDocId()["users/ayende"];

                $ranges = $cache["Heartrate"];

                $this->assertNotNull($ranges);
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 11), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 22), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 32), $ranges[1]->getTo());

                // should go to server to get [32, 35] and merge with [22, 32]
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 25), DateUtils::addMinutes($baseLine, 35));

                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                $this->assertCount(61, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 25), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 35), $vals[60]->getTimestamp());

                $ranges = $cache['Heartrate'];
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 11), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 22), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 35), $ranges[1]->getTo());

                // should go to server to get [20, 22] and [35, 40]
                // and merge them with [22, 35] into a single range [20, 40]
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 20), DateUtils::addMinutes($baseLine, 40));

                $this->assertEquals(5, $session->advanced()->getNumberOfRequests());

                $this->assertCount(121, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 20), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $vals[120]->getTimestamp());

                $ranges = $cache['Heartrate'];
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 11), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 20), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[1]->getTo());

                // should go to server to get [15, 20] and merge with [20, 40]
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 15), DateUtils::addMinutes($baseLine, 35));

                $this->assertEquals(6, $session->advanced()->getNumberOfRequests());

                $this->assertCount(121, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 15), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 35), $vals[120]->getTimestamp());

                $ranges = $cache['Heartrate'];
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 11), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 15), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[1]->getTo());

                // should go to server and add new cache entry for Heartrate2
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate2")
                        ->get($baseLine, DateUtils::addHours($baseLine, 2));

                $this->assertEquals(7, $session->advanced()->getNumberOfRequests());

                $this->assertCount(2, $vals);
                $this->assertEquals(DateUtils::addHours($baseLine, 1), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 90), $vals[1]->getTimestamp());

                $ranges2 = $cache["Heartrate2"];
                $this->assertCount(1, $ranges2);

                $this->assertEquals($baseLine, $ranges2[0]->getFrom());
                $this->assertEquals(DateUtils::addHours($baseLine, 2), $ranges2[0]->getTo());

                // should not go to server
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate2")
                        ->get(DateUtils::addMinutes($baseLine, 30), DateUtils::addMinutes($baseLine, 100));

                $this->assertEquals(7, $session->advanced()->getNumberOfRequests());
                $this->assertCount(2, $vals);
                $this->assertEquals(DateUtils::addHours($baseLine, 1), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 90), $vals[1]->getTimestamp());

                // should go to server
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 42), DateUtils::addMinutes($baseLine, 43));

                $this->assertEquals(8, $session->advanced()->getNumberOfRequests());

                $this->assertCount(7, $vals);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 42), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 43), $vals[6]->getTimestamp());

                $ranges = $cache['Heartrate'];
                $this->assertCount(3, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 11), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 15), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 40), $ranges[1]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 42), $ranges[2]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 43), $ranges[2]->getTo());

                // should go to server and to get the missing parts and merge all ranges into [0, 45]
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get($baseLine, DateUtils::addMinutes($baseLine, 45));

                $this->assertEquals(9, $session->advanced()->getNumberOfRequests());

                $this->assertCount(271, $vals);

                $this->assertEquals($baseLine, $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 45), $vals[270]->getTimestamp());

                $ranges = $cache["Heartrate"];
                $this->assertNotNull($ranges);
                $this->assertCount(1, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 0), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 45), $ranges[0]->getTo());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testShouldMergeTimeSeriesRangesInCache3(): void
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
                    $tsf->append(DateUtils::addSeconds($baseLine, $i * 10), 60, "watches/fitbit");
                }

                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");

                $tsf->append(DateUtils::addHours($baseLine, 1), 70, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 90), 75, "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 1), DateUtils::addMinutes($baseLine, 2));

                $this->assertCount(7, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[6]->getTimestamp());

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 5), DateUtils::addMinutes($baseLine, 6));

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $this->assertCount(7, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 6), $vals[6]->getTimestamp());

                /** @var InMemoryDocumentSessionOperations $s */
                $s = $session;
                $cache = $s->getTimeSeriesByDocId()["users/ayende"];
                $ranges = $cache["Heartrate"];
                $this->assertNotNull($ranges);
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 6), $ranges[1]->getTo());

                // should go to server to get [2, 3] and merge with [1, 2]

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 2), DateUtils::addMinutes($baseLine, 3));

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $this->assertCount(7, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $vals[6]->getTimestamp());

                $ranges = $cache['Heartrate'];
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 6), $ranges[1]->getTo());

                // should go to server to get [4, 5] and merge with [5, 6]
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 4), DateUtils::addMinutes($baseLine, 5));

                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                $this->assertCount(7, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 4), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 5), $vals[6]->getTimestamp());

                $ranges = $cache['Heartrate'];
                $this->assertCount(2, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $ranges[0]->getTo());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 4), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 6), $ranges[1]->getTo());

                // should go to server to get [3, 4] and merge all ranges into [1, 6]

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, 3), DateUtils::addMinutes($baseLine, 4));

                $this->assertEquals(5, $session->advanced()->getNumberOfRequests());

                $this->assertCount(7, $vals);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 4), $vals[6]->getTimestamp());

                $ranges = $cache['Heartrate'];
                $this->assertCount(1, $ranges);

                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 6), $ranges[0]->getTo());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanHandleRangesWithNoValues(): void {
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
                    $tsf->append(DateUtils::addSeconds($baseLine, $i * 10), 60, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addHours($baseLine, -2), DateUtils::addHours($baseLine, -1));

                $this->assertEmpty($vals);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // should not go to server
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addHours($baseLine, -2), DateUtils::addHours($baseLine, -1));

                $this->assertEmpty($vals);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // should not go to server
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, -90), DateUtils::addMinutes($baseLine, -70));

                $this->assertEmpty($vals);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // should go to server to get [-60, 1] and merge with [-120, -60]
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addHours($baseLine, -1), DateUtils::addMinutes($baseLine, 1));

                $this->assertCount(7, $vals);
                $this->assertEquals($baseLine, $vals[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $vals[6]->getTimestamp());
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                /** @var InMemoryDocumentSessionOperations $s */
                $s = $session;
                $cache = $s->getTimeSeriesByDocId()["users/ayende"];
                $ranges = $cache["Heartrate"];

                $this->assertNotNull($ranges);
                $this->assertCount(1, $ranges);

                $this->assertEquals(DateUtils::addHours($baseLine, -2), $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $ranges[0]->getTo());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
