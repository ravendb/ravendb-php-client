<?php

namespace tests\RavenDB\Test\Client\TimeSeries;

use DateTime;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeResultList;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntry;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntryArray;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class TimeSeriesSessionTest extends RemoteTestBase
{
    public function testCanCreateSimpleTimeSeries(): void
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
                        ->append(DateUtils::addMinutes($baseLine, 1), 59, "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var TimeSeriesEntry $val */
                 $val = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(null, null)[0];

                 $this->assertEquals([ 59 ], $val->getValues());
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
                $user->setName("Oren");
                $session->store($user, "users/ayende");

                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                $tsf->append(DateUtils::addMinutes($baseLine, 1), 59, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 2), 60, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 3), 61, "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(null, null);
                $this->assertCount(3, $val);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testTimeSeriesShouldBeCaseInsensitiveAndKeepOriginalCasing(): void
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
                        ->append(DateUtils::addMinutes($baseLine, 1), 59, "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->timeSeriesFor("users/ayende", "HeartRate")
                        ->append(DateUtils::addMinutes($baseLine, 2), 60, "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(get_class($user), "users/ayende");
                /** @var TimeSeriesEntryArray $val */
                $val = $session->timeSeriesFor("users/ayende", "heartrate")
                        ->get();

                $this->assertEquals([ 59 ], $val[0]->getValues());
                $this->assertEquals("watches/fitbit", $val[0]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $val[0]->getTimestamp());

                $this->assertEquals([ 60 ], $val[1]->getValues());
                $this->assertEquals("watches/fitbit", $val[1]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $val[1]->getTimestamp());

                $this->assertCount(1, $session->advanced()->getTimeSeriesFor($user));
                $this->assertContains("Heartrate", $session->advanced()->getTimeSeriesFor($user));

            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->timeSeriesFor("users/ayende", "HeartRatE")
                        ->delete();
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->timeSeriesFor("users/ayende", "HeArtRate")
                        ->append(DateUtils::addMinutes($baseLine, 3), 61, "watches/fitbit");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(get_class($user), "users/ayende");
                $vals = $session->timeSeriesFor("users/ayende", "heartrate")
                        ->get();
                $this->assertCount(1, $vals);

                /** @var TimeSeriesEntry $val */
                $val = $vals[0];

                $this->assertEquals([ 61 ], $val->getValues());
                $this->assertEquals("watches/fitbit", $val->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $val->getTimestamp());


                $timeSeries = $session->advanced()->getTimeSeriesFor($user);
                $this->assertCount(1, $timeSeries);
                $this->assertContains("HeArtRate", $timeSeries);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanDeleteTimestamp(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");

                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                $tsf->append(DateUtils::addMinutes($baseLine, 1), 59, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 2), 69, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 3), 79, "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");
                $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->deleteAt(DateUtils::addMinutes($baseLine, 2));

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var TimeSeriesEntryArray $vals */
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
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

    public function testUsingDifferentTags(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");

                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                $tsf->append(DateUtils::addMinutes($baseLine, 1), 59, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 2), 70, "watches/apple");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(null, null);

                $this->assertCount(2, $vals);

                $this->assertEquals([ 59 ], $vals[0]->getValues());
                $this->assertEquals("watches/fitbit", $vals[0]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $vals[0]->getTimestamp());

                $this->assertEquals([ 70 ], $vals[1]->getValues());
                $this->assertEquals("watches/apple", $vals[1]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[1]->getTimestamp());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testUsingDifferentNumberOfValues_SmallToLarge(): void
    {
        $store = $this->getDocumentStore();
        try {

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");

                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                $tsf->append(DateUtils::addMinutes($baseLine, 1), 59, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 2), [ 70, 120, 80 ], "watches/apple");
                $tsf->append(DateUtils::addMinutes($baseLine, 3), 69, "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(null, null);

                $this->assertCount(3, $vals);

                $this->assertEquals([ 59 ], $vals[0]->getValues());
                $this->assertEquals("watches/fitbit", $vals[0]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $vals[0]->getTimestamp());

                $this->assertEquals([ 70, 120, 80 ], $vals[1]->getValues());
                $this->assertEquals("watches/apple", $vals[1]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[1]->getTimestamp());

                $this->assertEquals([ 69 ], $vals[2]->getValues());
                $this->assertEquals("watches/fitbit", $vals[2]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $vals[2]->getTimestamp());
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
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");

                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                $tsf->append(DateUtils::addMinutes($baseLine, 1), [ 70, 120, 80 ], "watches/apple");
                $tsf->append(DateUtils::addMinutes($baseLine, 2), 59, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 3), 69, "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(null, null);

                $this->assertCount(3, $vals);

                $this->assertEquals([ 70, 120, 80 ], $vals[0]->getValues());
                $this->assertEquals("watches/apple", $vals[0]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $vals[0]->getTimestamp());

                $this->assertEquals([ 59 ], $vals[1]->getValues());
                $this->assertEquals("watches/fitbit", $vals[1]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[1]->getTimestamp());

                $this->assertEquals([ 69 ], $vals[2]->getValues());
                $this->assertEquals("watches/fitbit", $vals[2]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $vals[2]->getTimestamp());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanStoreAndReadMultipleTimestamps(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");

                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                $tsf->append(DateUtils::addMinutes($baseLine, 1), [ 59 ], "watches/fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                $tsf->append(DateUtils::addMinutes($baseLine, 2), 61, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 3), 62, "watches/apple-watch");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(null, null);

                $this->assertCount(3, $vals);

                $this->assertEquals([ 59 ], $vals[0]->getValues());
                $this->assertEquals("watches/fitbit", $vals[0]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 1), $vals[0]->getTimestamp());

                $this->assertEquals([ 61 ], $vals[1]->getValues());
                $this->assertEquals("watches/fitbit", $vals[1]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 2), $vals[1]->getTimestamp());

                $this->assertEquals([ 62 ], $vals[2]->getValues());
                $this->assertEquals("watches/apple-watch", $vals[2]->getTag());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 3), $vals[2]->getTimestamp());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanStoreLargeNumberOfValues(): void
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

            $offset = 0;

            for ($i = 0; $i < 10; $i++) {
                $session = $store->openSession();
                try {
                    $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                    for ($j = 0; $j < 1000; $j++) {
                        $tsf->append(DateUtils::addMinutes($baseLine, $offset++), [ $offset ], "watches/fitbit");
                    }

                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(null, null);

                $this->assertCount(10000, $vals);

                for ($i = 0; $i < 10000; $i++) {
                    $this->assertEquals(DateUtils::addMinutes($baseLine, $i), $vals[$i]->getTimestamp());
                    $this->assertEquals(1 + $i, $vals[$i]->getValues()[0]);
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanStoreValuesOutOfOrder(): void
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

            $retries = 1000;

            $offset = 0;

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                for ($j = 0; $j < $retries; $j++) {

                    $tsf->append(DateUtils::addMinutes($baseLine, $offset), [ $offset ], "watches/fitbit");

                    $offset += 5;
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $offset = 1;

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                $vals = $tsf->get(null, null);
                $this->assertCount($retries, $vals);


                for ($j = 0; $j < $retries; $j++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $offset), [ $offset ], "watches/fitbit");
                    $offset += 5;
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(null, null);

                $this->assertCount(2 * $retries, $vals);

                $offset = 0;
                for ($i = 0; $i < $retries; $i++) {
                    $this->assertEquals(DateUtils::addMinutes($baseLine, $offset), $vals[$i]->getTimestamp());
                    $this->assertEquals($offset, $vals[$i]->getValues()[0]);

                    $offset++;
                    $i++;

                    $this->assertEquals(DateUtils::addMinutes($baseLine, $offset), $vals[$i]->getTimestamp());
                    $this->assertEquals($offset, $vals[$i]->getValues()[0]);

                    $offset += 4;
                }
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
                $user->setName("Oren");
                $session->store($user, "users/ayende");
                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");
                $tsf->append($baseLine, 58, "watches/fitbit");
                $tsf->append(DateUtils::addMinutes($baseLine, 10), 60, "watches/fitbit");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var array<TimeSeriesEntry>  $vals */
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(DateUtils::addMinutes($baseLine, -10), DateUtils::addMinutes($baseLine, -5));

                $this->assertEmpty($vals);

                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
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
        $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, "users/karmel");
                $session->timeSeriesFor("users/karmel", "Nasdaq2")
                        ->append(new DateTime(), 7547.31, "web");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->timeSeriesFor("users/karmel", "Heartrate2")
                        ->append(new DateTime(), 7547.31, "web");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->store(new User(), "users/ayende");
                $session->timeSeriesFor("users/ayende", "Nasdaq")
                        ->append(new DateTime(), 7547.31, "web");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->append(DateUtils::addMinutes(new DateTime(), 1), 58, "fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(get_class($user), "users/ayende");
                $tsNames = $session->advanced()->getTimeSeriesFor($user);
                $this->assertCount(2, $tsNames);

                // should be sorted
                $this->assertEquals("Heartrate", $tsNames[0]);
                $this->assertEquals("Nasdaq", $tsNames[1]);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->timeSeriesFor("users/ayende", "heartrate")  // putting ts name as lower cased
                    ->append(DateUtils::addMinutes($baseLine, 1), 58, "fitbit");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(get_class($user), "users/ayende");
                $tsNames = $session->advanced()->getTimeSeriesFor($user);
                $this->assertCount(2, $tsNames);

                // should preserve original casing
                $this->assertEquals("Heartrate", $tsNames[0]);
                $this->assertEquals("Nasdaq", $tsNames[1]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetTimeSeriesNames2(): void
    {
        $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $offset = 0;

            for ($i = 0; $i < 100; $i++) {
                $sessions = $store->openSession();
                try {
                    $tsf = $sessions->timeSeriesFor("users/ayende", "Heartrate");

                    for ($j = 0; $j < 1000; $j++) {
                        $tsf->append(DateUtils::addMinutes($baseLine, $offset++), $offset, "watches/fitbit");
                    }

                    $sessions->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $offset = 0;

            for ($i = 0; $i < 100; $i++) {
                $session = $store->openSession();
                try {
                    $tsf = $session->timeSeriesFor("users/ayende", "Pulse");

                    for ($j = 0; $j < 1000; $j++) {
                        $tsf->append(DateUtils::addMinutes($baseLine, $offset++), $offset, "watches/fitbit");
                    }
                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(null, null);

                $this->assertCount(100000, $vals);

                for ($i = 0; $i < 100000; $i++) {
                    $this->assertEquals(DateUtils::addMinutes($baseLine, $i), $vals[$i]->getTimestamp());
                    $this->assertEquals(1 + $i, $vals[$i]->getValues()[0]);
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Pulse")
                        ->get(null, null);
                $this->assertCount(100000, $vals);

                for ($i = 0; $i < 100000; $i++) {
                    $this->assertEquals(DateUtils::addMinutes($baseLine, $i), $vals[$i]->getTimestamp());
                    $this->assertEquals(1 + $i, $vals[$i]->getValue());
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(get_class($user), "users/ayende");
                $tsNames = $session->advanced()->getTimeSeriesFor($user);

//                 should be sorted
                $this->assertEquals("Heartrate", $tsNames[0]);
                $this->assertEquals("Pulse", $tsNames[1]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testsShouldDeleteTimeSeriesUponDocumentDeletion(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $id = "users/ayende";

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, $id);

                $timeSeriesFor = $session->timeSeriesFor($id, "Heartrate");
                $timeSeriesFor->append(DateUtils::addMinutes($baseLine, 1), 59, "watches/fitbit");
                $timeSeriesFor->append(DateUtils::addMinutes($baseLine, 2), 59, "watches/fitbit");

                $session->timeSeriesFor($id, "Heartrate2")
                        ->append(DateUtils::addMinutes($baseLine, 1), 59, "watches/apple");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->delete($id);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var array<TimeSeriesEntry> $vals */
                $vals = $session
                    ->timeSeriesFor($id, "Heartrate")
                    ->get(null, null);

                $this->assertNull($vals);

                $vals = $session->timeSeriesFor($id, "Heartrate2")
                        ->get(null, null);
                $this->assertNull($vals);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanSkipAndTakeTimeSeries(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/ayende");

                $tsf = $session->timeSeriesFor("users/ayende", "Heartrate");

                for ($i = 0; $i < 100; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), 100 + $i, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $vals = $session->timeSeriesFor("users/ayende", "Heartrate")
                        ->get(null, null, null, 5, 20);

                $this->assertCount(20, $vals);

                for ($i = 0; $i < count($vals); $i++) {
                    $this->assertEquals(DateUtils::addMinutes($baseLine, 5 + $i), $vals[$i]->getTimestamp());
                    $this->assertEquals(105 + $i, $vals[$i]->getValue());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testShouldEvictTimeSeriesUponEntityEviction(): void
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

                $tsf = $session->timeSeriesFor($documentId, "Heartrate");

                for ($i = 0; $i < 60; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), 100 + $i, "watches/fitbit");
                }

                $tsf = $session->timeSeriesFor($documentId, "BloodPressure");

                for ($i = 0; $i < 10; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), [ 120 - $i, 80 + $i], "watches/apple");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(get_class($user), $documentId);

                $tsf = $session->timeSeriesFor($user, "Heartrate");

                $vals = $tsf->get($baseLine, DateUtils::addMinutes($baseLine, 10));

                $this->assertCount(11, $vals);

                $vals = $tsf->get(DateUtils::addMinutes($baseLine, 20), DateUtils::addMinutes($baseLine, 50));

                $this->assertCount(31, $vals);

                $tsf = $session->timeSeriesFor($user, "BloodPressure");
                $vals = $tsf->get();

                $this->assertCount(10, $vals);

                /** @var InMemoryDocumentSessionOperations $sessionOperations */
                $sessionOperations = $session;

                $this->assertCount(1, $sessionOperations->getTimeSeriesByDocId());

                /** @var array<TimeSeriesRangeResultList> $cache */
                $cache = $sessionOperations->getTimeSeriesByDocId()[$documentId];
                $this->assertCount(2, $cache);

                /** @var TimeSeriesRangeResultList $ranges */
                $ranges = $cache["Heartrate"];
                $this->assertCount(2, $ranges);

                $this->assertEquals($baseLine, $ranges[0]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $ranges[0]->getTo());
                $this->assertCount(11, $ranges[0]->getEntries());

                $this->assertEquals(DateUtils::addMinutes($baseLine, 20), $ranges[1]->getFrom());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 50), $ranges[1]->getTo());
                $this->assertCount(31, $ranges[1]->getEntries());

                /** @var TimeSeriesRangeResultList $ranges */
                $ranges = $cache["BloodPressure"];
                $this->assertCount(1, $ranges);

                $this->assertNull($ranges[0]->getFrom());
                $this->assertNull($ranges[0]->getTo());
                $this->assertCount(10, $ranges[0]->getEntries());

                $session->advanced()->evict($user);

                $this->assertArrayNotHasKey($documentId, $sessionOperations->getTimeSeriesByDocId());
                $this->assertEmpty($sessionOperations->getTimeSeriesByDocId());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testGetAllTimeSeriesNamesWhenNoTimeSeriesTimeSeriesTypedSessionTest(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, "users/karmel");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(get_class($user), "users/karmel");
                $tsNames = $session->advanced()->getTimeSeriesFor($user);
                $this->assertEmpty($tsNames);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testGetSingleTimeSeriesWhenNoTimeSeries(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, "users/karmel");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(get_class($user), "users/karmel");
                $ts = $session->timeSeriesFor($user, "unicorns")
                        ->get();
                $this->assertNull($ts);
            } finally {
                $session->close();
            }
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

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor($docId, "Heartrate");

                $entries = $tsf->get();
                $this->assertCount(100, $entries);

                // null From, To
                $tsf->delete();
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $entries = $session->timeSeriesFor($docId, "Heartrate")->get();
                $this->assertNull($entries);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor($docId, "BloodPressure");

                $entries = $tsf->get();
                $this->assertCount(100, $entries);

                // null to
                $tsf->delete(DateUtils::addMinutes($baseLine, 50), null);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $entries = $session->timeSeriesFor($docId, "BloodPressure")
                        ->get();
                $this->assertCount(50, $entries);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor($docId, "BodyTemperature");

                $entries = $tsf->get();
                $this->assertCount(100, $entries);

                // null from
                $tsf->delete(null, DateUtils::addMinutes($baseLine, 19));
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $entries = $session->timeSeriesFor($docId, "BodyTemperature")
                        ->get();

                $this->assertCount(80, $entries);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
