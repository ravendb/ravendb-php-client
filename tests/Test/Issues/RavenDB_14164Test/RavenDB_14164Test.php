<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14164Test;

use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RavenTestHelper;
use tests\RavenDB\RemoteTestBase;

class RavenDB_14164Test extends RemoteTestBase
{
    public function testCanGetTimeSeriesWithIncludeTagDocuments(): void
    {
        $store = $this->getDocumentStore();
        try {
            $tags = [ "watches/fitbit", "watches/apple", "watches/sony" ];
            $baseline = RavenTestHelper::utcToday();
            $documentId = "users/ayende";

            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);

                $watch1 = new Watch();
                $watch1->setName("FitBit");
                $watch1->setAccuracy(0.855);
                $session->store($watch1, $tags[0]);

                $watch2 = new Watch();
                $watch2->setName("Apple");
                $watch2->setAccuracy(0.9);
                $session->store($watch2, $tags[1]);

                $watch3 = new Watch();
                $watch3->setName("Sony");
                $watch3->setAccuracy(0.78);
                $session->store($watch3, $tags[2]);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor($documentId, "heartRate");

                for ($i = 0; $i <= 120; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseline, $i), $i, $tags[$i % 3]);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $getResults = $session->timeSeriesFor($documentId, "heartRate")
                        ->get($baseline, DateUtils::addHours($baseline, 2), function($builder) { $builder->includeTags();});

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(121, $getResults);
                $this->assertEquals($baseline, $getResults[0]->getTimestamp());
                $this->assertEquals(DateUtils::addHours($baseline, 2), $getResults[count($getResults) - 1]->getTimestamp());

                // should not go to server

                $tagDocuments = $session->load(Watch::class, $tags);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // assert tag documents

                $this->assertCount(3, $tagDocuments);

                $tagDoc = $tagDocuments["watches/fitbit"];
                $this->assertEquals("FitBit", $tagDoc->getName());
                $this->assertEquals(0.855, $tagDoc->getAccuracy());

                $tagDoc = $tagDocuments["watches/apple"];
                $this->assertEquals("Apple", $tagDoc->getName());
                $this->assertEquals(0.9, $tagDoc->getAccuracy());

                $tagDoc = $tagDocuments["watches/sony"];
                $this->assertEquals("Sony", $tagDoc->getName());
                $this->assertEquals(0.78, $tagDoc->getAccuracy());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetTimeSeriesWithIncludeTagsAndParentDocument(): void
    {
        $store = $this->getDocumentStore();
        try {
            $tags = [ "watches/fitbit", "watches/apple", "watches/sony" ];
            $baseline = RavenTestHelper::utcToday();

            $documentId = "users/ayende";
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("ayende");

                $session->store($user, $documentId);

                $watch1 = new Watch();
                $watch1->setName("FitBit");
                $watch1->setAccuracy(0.855);
                $session->store($watch1, $tags[0]);

                $watch2 = new Watch();
                $watch2->setName("Apple");
                $watch2->setAccuracy(0.9);
                $session->store($watch2, $tags[1]);

                $watch3 = new Watch();
                $watch3->setName("Sony");
                $watch3->setAccuracy(0.78);
                $session->store($watch3, $tags[2]);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor($documentId, "heartRate");

                for ($i = 0; $i <= 120; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseline, $i), $i, $tags[$i % 3]);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $getResults = $session->timeSeriesFor($documentId, "heartRate")
                        ->get($baseline, DateUtils::addHours($baseline, 2), function($builder) { $builder->includeTags()->includeDocument(); });

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(121, $getResults);

                $this->assertEquals($baseline, $getResults[0]->getTimestamp());
                $this->assertEquals(DateUtils::addHours($baseline, 2), $getResults[count($getResults) - 1]->getTimestamp());

                // should not go to server

                $user = $session->load(User::class, $documentId);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertEquals("ayende", $user->getName());

                // should not go to server

                $tagDocuments = $session->load(Watch::class, $tags);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // assert tag documents

                $this->assertCount(3, $tagDocuments);

                $tagDoc = $tagDocuments["watches/fitbit"];
                $this->assertEquals("FitBit", $tagDoc->getName());
                $this->assertEquals(0.855, $tagDoc->getAccuracy());

                $tagDoc = $tagDocuments["watches/apple"];
                $this->assertEquals("Apple", $tagDoc->getName());
                $this->assertEquals(0.9, $tagDoc->getAccuracy());

                $tagDoc = $tagDocuments["watches/sony"];
                $this->assertEquals("Sony", $tagDoc->getName());
                $this->assertEquals(0.78, $tagDoc->getAccuracy());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetTimeSeriesWithInclude_CacheNotEmpty(): void
    {
        $store = $this->getDocumentStore();
        try {
            $tags = [ "watches/fitbit", "watches/apple", "watches/sony" ];
            $baseline = RavenTestHelper::utcToday();

            $documentId = "users/ayende";
            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);

                $watch1 = new Watch();
                $watch1->setName("FitBit");
                $watch1->setAccuracy(0.855);
                $session->store($watch1, $tags[0]);

                $watch2 = new Watch();
                $watch2->setName("Apple");
                $watch2->setAccuracy(0.9);
                $session->store($watch2, $tags[1]);

                $watch3 = new Watch();
                $watch3->setName("Sony");
                $watch3->setAccuracy(0.78);
                $session->store($watch3, $tags[2]);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor($documentId, "heartRate");

                for ($i = 0; $i <= 120; $i++) {
                    $tag = '';
                    if ($i < 60) {
                        $tag = $tags[0];
                    } else if ($i < 90) {
                        $tag = $tags[1];
                    } else {
                        $tag = $tags[2];
                    }

                    $tsf->append(DateUtils::addMinutes($baseline, $i), $i, $tag);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                // get [00:00 - 01:00]
                $getResults = $session->timeSeriesFor($documentId, "heartRate")
                        ->get($baseline, DateUtils::addHours($baseline, 1));

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(61, $getResults);
                $this->assertEquals($baseline, $getResults[0]->getTimestamp());
                $this->assertEquals(DateUtils::addHours($baseline, 1), $getResults[count($getResults) - 1]->getTimestamp());

                // get [01:15 - 02:00] with includes
                $getResults = $session->timeSeriesFor($documentId, "heartRate")
                        ->get(DateUtils::addMinutes($baseline, 75), DateUtils::addHours($baseline, 2),
                                function($builder) { $builder->includeTags(); });

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $this->assertCount(46, $getResults);
                $this->assertEquals(DateUtils::addMinutes($baseline, 75), $getResults[0]->getTimestamp());
                $this->assertEquals(DateUtils::addHours($baseline, 2), $getResults[count($getResults) - 1]->getTimestamp());

                // should not go to server

                $tagsDocuments = $session->load(Watch::class, [ $tags[1], $tags[2]]);
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                // assert tag documents
                $this->assertCount(2, $tagsDocuments);

                $tagDoc = $tagsDocuments["watches/apple"];
                $this->assertEquals("Apple", $tagDoc->getName());
                $this->assertEquals(0.9, $tagDoc->getAccuracy());

                $tagDoc = $tagsDocuments["watches/sony"];
                $this->assertEquals("Sony", $tagDoc->getName());
                $this->assertEquals(0.78, $tagDoc->getAccuracy());

                // "watches/fitbit" should not be in cache

                $watch = $session->load(Watch::class, $tags[0]);
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $this->assertEquals("FitBit", $watch->getName());
                $this->assertEquals(0.855, $watch->getAccuracy());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function canGetTimeSeriesWithInclude_CacheNotEmpty2(): void
    {
        $store = $this->getDocumentStore();
        try {
//            $tags = [ "watches/fitbit", "watches/apple", "watches/sony" ];
//            $baseline = RavenTestHelper::utcToday();
//
//            $documentId = "users/ayende";
            $session = $store->openSession();
            try {
//                $session->store(new User(), $documentId);
//
//                $watch1 = new Watch();
//                $watch1->setName("FitBit");
//                $watch1->setAccuracy(0.855);
//                $session->store($watch1, $tags[0]);
//
//                $watch2 = new Watch();
//                $watch2->setName("Apple");
//                $watch2->setAccuracy(0.9);
//                $session->store($watch2, $tags[1]);
//
//                $watch3 = new Watch();
//                $watch3->setName("Sony");
//                $watch3->setAccuracy(0.78);
//                $session->store($watch3, $tags[2]);
//
//                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
//                $tsf = $session->timeSeriesFor($documentId, "heartRate");
//
//                for ($i = 0; $i <= 120; $i++) {
//                    String tag;
//                    if (i < 60) {
//                        tag = tags[0];
//                    } else if (i < 90) {
//                        tag = tags[1];
//                    } else {
//                        tag = tags[2];
//                    }
//
//                    $tsf->append(DateUtils::addMinutes($baseline, $i), $i, tag);
//                }
//
//                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
//                // get [00:00 - 01:00]
//                $getResults = $session->timeSeriesFor($documentId, "heartRate")
//                        ->get($baseline, DateUtils::addHours($baseline, 1));
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                $this->assertCount(121, $getResults);
//                        .hasSize(61);
//                assertThat($getResults[0]->getTimestamp())
//                        .isEqualTo($baseline);
//                assertThat($getResults[count($getResults) - 1]->getTimestamp())
//                        .isEqualTo(DateUtils::addHours($baseline, 1));
//
//                // get [01:30 - 02:00]
//                $getResults = $session->timeSeriesFor($documentId, "heartRate")
//                        ->get(DateUtils::addMinutes($baseline, 90), DateUtils::addHours($baseline, 2));
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(2);
//
//                $this->assertCount(121, $getResults);
//                        .hasSize(31);
//                assertThat($getResults[0]->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseline, 90));
//                assertThat($getResults[count($getResults) - 1]->getTimestamp())
//                        .isEqualTo(DateUtils::addHours($baseline, 2));
//
//                // get [01:00 - 01:15] with includes
//                $getResults = $session->timeSeriesFor($documentId, "heartRate")
//                        ->get(DateUtils::addHours($baseline, 1), DateUtils::addMinutes($baseline, 75),
//                                builder -> builder.includeTags());
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(3);
//
//                $this->assertCount(121, $getResults);
//                        .hasSize(16);
//                assertThat($getResults[0]->getTimestamp())
//                        .isEqualTo(DateUtils::addHours($baseline, 1));
//                assertThat($getResults[count($getResults) - 1]->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseline, 75));
//
//                // should not go to server
//
//                $watch = $session->load(Watch::class, $tags[1]);
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(3);
//
//                assertThat($watch.getName())
//                        .isEqualTo("Apple");
//
//                assertThat($watch.getAccuracy())
//                        .isEqualTo(0.9);
//
//                // tags[0] and tags[2] should not be in cache
//
//                watch = $session->load(Watch::class, $tags[0]);
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(4);
//
//                assertThat($watch.getName())
//                        .isEqualTo("FitBit");
//                assertThat($watch.getAccuracy())
//                        .isEqualTo(0.855);
//
//                watch = $session->load(Watch::class, $tags[2]);
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(5);
//                assertThat($watch.getName())
//                        .isEqualTo("Sony");
//                assertThat($watch.getAccuracy())
//                        .isEqualTo(0.78);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function canGetMultipleRangesWithIncludes(): void
    {
        $store = $this->getDocumentStore();
        try {
//            $tags = [ "watches/fitbit", "watches/apple", "watches/sony" ];
//            $baseline = RavenTestHelper::utcToday();
//
//            $documentId = "users/ayende";

            $session = $store->openSession();
            try {
//                $user = new User();
//                $user->setName("ayende");
//                $session->store($user, $documentId);
//
//                $watch1 = new Watch();
//                $watch1->setName("FitBit");
//                $watch1->setAccuracy(0.855);
//                $session->store($watch1, $tags[0]);
//
//                $watch2 = new Watch();
//                $watch2->setName("Apple");
//                $watch2->setAccuracy(0.9);
//                $session->store($watch2, $tags[1]);
//
//                $watch3 = new Watch();
//                $watch3->setName("Sony");
//                $watch3->setAccuracy(0.78);
//                $session->store($watch3, $tags[2]);
//
//                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
//                $tsf = $session->timeSeriesFor($documentId, "heartRate");
//
//                for ($i = 0; $i <= 120; $i++) {
//                    $tsf->append(DateUtils::addMinutes($baseline, $i), $i, $tags[$i % 3]);
//                }
//
//                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
//                // get range [00:00 - 00:30]
//                $getResults = $session->timeSeriesFor($documentId, "heartRate")
//                        ->get($baseline, DateUtils::addMinutes($baseline, 30));
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(1);
//
//                $this->assertCount(121, $getResults);
//                        .hasSize(31);
//                assertThat($getResults[0]->getTimestamp())
//                        .isEqualTo($baseline);
//                assertThat($getResults[count($getResults) - 1]->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseline, 30));
//
//                // get range [00:45 - 00:60]
//
//                $getResults = $session->timeSeriesFor($documentId, "heartRate")
//                        ->get(DateUtils::addMinutes($baseline, 45), DateUtils::addHours($baseline, 1));
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(2);
//                $this->assertCount(121, $getResults);
//                        .hasSize(16);
//                assertThat($getResults[0]->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseline, 45));
//                assertThat($getResults[count($getResults) - 1]->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseline, 60));
//
//                // get range [01:30 - 02:00]
//                $getResults = $session->timeSeriesFor($documentId, "heartRate")
//                        ->get(DateUtils::addMinutes($baseline, 90), DateUtils::addHours($baseline, 2));
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(3);
//
//                $this->assertCount(121, $getResults);
//                        .hasSize(31);
//
//                assertThat($getResults[0]->getTimestamp())
//                        .isEqualTo(DateUtils::addMinutes($baseline, 90));
//                assertThat($getResults[count($getResults) - 1]->getTimestamp())
//                        .isEqualTo(DateUtils::addHours($baseline, 2));
//
//                // get multiple ranges with includes
//                // ask for entire range [00:00 - 02:00] with includes
//                // this will go to server to get the "missing parts" - [00:30 - 00:45] and [01:00 - 01:30]
//
//                $getResults = $session->timeSeriesFor($documentId, "heartRate")
//                        ->get($baseline, DateUtils::addHours($baseline, 2),
//                                builder -> builder.includeTags().includeDocument());
//
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(4);
//                $this->assertCount(121, $getResults);
//                        .hasSize(121);
//
//                assertThat($getResults[0]->getTimestamp())
//                        .isEqualTo($baseline);
//                assertThat($getResults[count($getResults) - 1]->getTimestamp())
//                        .isEqualTo(DateUtils::addHours($baseline, 2));
//
//                // should not go to server
//
//                $user = $session->load(User::class, $documentId);
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(4);
//                assertThat($user.getName())
//                        .isEqualTo("ayende");
//
//                // should not go to server
//                $tagDocuments = $session->load(Watch::class, $tags);
//                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
//                        .isEqualTo(4);
//
//                // assert tag documents
//
//                assertThat($tagDocuments)
//                        .hasSize(3);
//
//                $tagDoc = $tagDocuments["watches/fitbit"];
//                assertThat($tagDoc->getName())
//                        .isEqualTo("FitBit");
//                assertThat($tagDoc->getAccuracy())
//                        .isEqualTo(0.855);
//
//                $tagDoc = $tagDocuments["watches/apple"];
//                assertThat($tagDoc->getName())
//                        .isEqualTo("Apple");
//                assertThat($tagDoc->getAccuracy())
//                        .isEqualTo(0.9);
//
//                $tagDoc = $tagDocuments["watches/sony"];
//                assertThat($tagDoc->getName())
//                        .isEqualTo("Sony");
//                assertThat($tagDoc->getAccuracy())
//                        .isEqualTo(0.78);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetTimeSeriesWithIncludeTags_WhenNotAllEntriesHaveTags(): void
    {
        $store = $this->getDocumentStore();
        try {
            $tags = [ "watches/fitbit", "watches/apple", "watches/sony" ];
            $baseline = RavenTestHelper::utcToday();

            $documentId = "users/ayende";
            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, $documentId);

                $watch1 = new Watch();
                $watch1->setName("FitBit");
                $watch1->setAccuracy(0.855);
                $session->store($watch1, $tags[0]);

                $watch2 = new Watch();
                $watch2->setName("Apple");
                $watch2->setAccuracy(0.9);
                $session->store($watch2, $tags[1]);

                $watch3 = new Watch();
                $watch3->setName("Sony");
                $watch3->setAccuracy(0.78);
                $session->store($watch3, $tags[2]);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor($documentId, "heartRate");

                for ($i = 0; $i <= 120; $i++) {
                    $tag = $i % 10 == 0
                            ? null
                            : $tags[$i % 3];
                    $tsf->append(DateUtils::addMinutes($baseline, $i), $i, $tag);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $getResults = $session->timeSeriesFor($documentId, "heartRate")
                        ->get($baseline, DateUtils::addHours($baseline, 2),
                                function($builder) { $builder->includeTags(); } );

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(121, $getResults);
                $this->assertEquals($baseline, $getResults[0]->getTimestamp());
                $this->assertEquals(DateUtils::addHours($baseline, 2), $getResults[count($getResults) - 1]->getTimestamp());

                // should not go to server
                $tagDocuments = $session->load(Watch::class, $tags);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // assert tag documents

                $this->assertCount(3, $tagDocuments);

                $tagDoc = $tagDocuments["watches/fitbit"];
                $this->assertEquals("FitBit", $tagDoc->getName());
                $this->assertEquals(0.855, $tagDoc->getAccuracy());

                $tagDoc = $tagDocuments["watches/apple"];
                $this->assertEquals("Apple", $tagDoc->getName());
                $this->assertEquals(0.9, $tagDoc->getAccuracy());

                $tagDoc = $tagDocuments["watches/sony"];
                $this->assertEquals("Sony", $tagDoc->getName());
                $this->assertEquals(0.78, $tagDoc->getAccuracy());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testIncludesShouldAffectTimeSeriesGetCommandEtag(): void
    {
        $store = $this->getDocumentStore();
        try {
            $tags = [ "watches/fitbit", "watches/apple", "watches/sony" ];
            $baseline = RavenTestHelper::utcToday();

            $documentId = "users/ayende";
            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, $documentId);

                $watch1 = new Watch();
                $watch1->setName("FitBit");
                $watch1->setAccuracy(0.855);
                $session->store($watch1, $tags[0]);

                $watch2 = new Watch();
                $watch2->setName("Apple");
                $watch2->setAccuracy(0.9);
                $session->store($watch2, $tags[1]);

                $watch3 = new Watch();
                $watch3->setName("Sony");
                $watch3->setAccuracy(0.78);
                $session->store($watch3, $tags[2]);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $tsf = $session->timeSeriesFor($documentId, "heartRate");

                for ($i = 0; $i <= 120; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseline, $i), $i, $tags[$i % 3]);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $getResults = $session->timeSeriesFor($documentId, "heartRate")
                        ->get($baseline, DateUtils::addHours($baseline, 2),
                                function($builder) { $builder->includeTags(); });

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(121, $getResults);
                $this->assertEquals($baseline, $getResults[0]->getTimestamp());
                $this->assertEquals(DateUtils::addHours($baseline, 2), $getResults[count($getResults) - 1]->getTimestamp());

                // should not go to server

                $tagDocuments = $session->load(Watch::class, $tags);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // assert tag documents

                $this->assertCount(3, $tagDocuments);

                $tagDoc = $tagDocuments["watches/fitbit"];
                $this->assertEquals("FitBit", $tagDoc->getName());
                $this->assertEquals(0.855, $tagDoc->getAccuracy());

                $tagDoc = $tagDocuments["watches/apple"];
                $this->assertEquals("Apple", $tagDoc->getName());
                $this->assertEquals(0.9, $tagDoc->getAccuracy());

                $tagDoc = $tagDocuments["watches/sony"];
                $this->assertEquals("Sony", $tagDoc->getName());
                $this->assertEquals(0.78, $tagDoc->getAccuracy());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                // update tags[0]

                $watch = $session->load(Watch::class, $tags[0]);
                $watch->setAccuracy($watch->getAccuracy() + 0.05);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $getResults = $session->timeSeriesFor($documentId, "heartRate")
                        ->get($baseline, DateUtils::addHours($baseline, 2),
                                function($builder) { $builder->includeTags(); });

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(121, $getResults);
                $this->assertEquals($baseline, $getResults[0]->getTimestamp());
                $this->assertEquals(DateUtils::addHours($baseline, 2), $getResults[count($getResults) - 1]->getTimestamp());

                // should not go to server

                $tagDocuments = $session->load(Watch::class, $tags);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // assert tag documents

                $this->assertCount(3, $tagDocuments);

                $tagDoc = $tagDocuments["watches/fitbit"];
                $this->assertEquals("FitBit", $tagDoc->getName());
                $this->assertEquals(0.905, $tagDoc->getAccuracy());
            } finally {
                $session->close();
            }
            $newTag = "watches/google";

            $session = $store->openSession();
            try {
                // add new watch

                $watch = new Watch();
                $watch->setAccuracy(0.75);
                $watch->setName("Google Watch");

                $session->store($watch, $newTag);

                // update a time series entry to have the new tag

                $session->timeSeriesFor($documentId, "heartRate")
                        ->append(DateUtils::addMinutes($baseline, 45), 90, $newTag);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $getResults = $session->timeSeriesFor($documentId, "heartRate")
                        ->get($baseline, DateUtils::addHours($baseline, 2),
                                function($builder) { $builder->includeTags();});

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(121, $getResults);
                $this->assertEquals($baseline, $getResults[0]->getTimestamp());
                $this->assertEquals(DateUtils::addHours($baseline, 2), $getResults[count($getResults) - 1]->getTimestamp());

                // should not go to server
                $session->load(Watch::class, $tags);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // assert that newTag is in cache
                $watch = $session->load(Watch::class, $newTag);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertEquals("Google Watch", $watch->getName());
                $this->assertEquals(0.75, $watch->getAccuracy());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
