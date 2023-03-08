<?php

namespace tests\RavenDB\Counters;

use Exception;
use Ramsey\Uuid\Uuid;
use RavenDB\Documents\Operations\Counters\CounterBatch;
use RavenDB\Documents\Operations\Counters\CounterBatchOperation;
use RavenDB\Documents\Operations\Counters\CounterOperation;
use RavenDB\Documents\Operations\Counters\CounterOperationType;
use RavenDB\Documents\Operations\Counters\DocumentCountersOperation;
use RavenDB\Documents\Operations\Counters\GetCountersOperation;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\ServerWide\DatabaseRecord;
use RavenDB\ServerWide\Operations\CreateDatabaseOperation;
use RavenDB\ServerWide\Operations\DeleteDatabasesOperation;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\Employee;
use tests\RavenDB\Infrastructure\Entity\Order;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class SessionCountersTest extends RemoteTestBase
{
    public function testSessionIncrementCounter(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Aviv1");

                $user2 = new User();
                $user2->setName("Aviv2");

                $session->store($user1, "users/1-A");
                $session->store($user2, "users/2-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("downloads", 500);
                $session->countersFor("users/2-A")->increment("votes", 1000);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $counters = $store->operations()->send(new GetCountersOperation("users/1-A", ["likes", "downloads"]))
                ->getCounters();

            $this->assertCount(2, $counters);


            $likes = array_filter($counters->getArrayCopy(), function ($x) {
                return $x->getCounterName() == 'likes';
            });
            $this->assertEquals(100, $likes[array_key_first($likes)]->getTotalValue());

            $downloads = array_filter($counters->getArrayCopy(), function ($x) {
                return $x->getCounterName() == 'downloads';
            });
            $this->assertEquals(500, $downloads[array_key_first($downloads)]->getTotalValue());

            $counters = $store->operations()->send(new GetCountersOperation("users/2-A", ["votes"]))
                ->getCounters();

            $this->assertCount(1, $counters);

            $votes = array_filter($counters->getArrayCopy(), function ($x) {
                return $x->getCounterName() == 'votes';
            });
            $this->assertEquals(1000, $votes[array_key_first($votes)]->getTotalValue());
        } finally {
            $store->close();
        }
    }

    public function testSessionDeleteCounter(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Aviv1");

                $user2 = new User();
                $user2->setName("Aviv2");

                $session->store($user1, "users/1-A");
                $session->store($user2, "users/2-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("downloads", 500);
                $session->countersFor("users/2-A")->increment("votes", 1000);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $counters = $store->operations()
                ->send(new GetCountersOperation("users/1-A", ["likes", "downloads"]))
                ->getCounters();

            $this->assertCount(2, $counters);

            $session = $store->openSession();
            try {
                $session->countersFor("users/1-A")->delete("likes");
                $session->countersFor("users/1-A")->delete("downloads");
                $session->countersFor("users/2-A")->delete("votes");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $counters = $store->operations()
                ->send(new GetCountersOperation("users/1-A", ["likes", "downloads"]))
                ->getCounters();

            $this->assertCount(2, $counters);
            $this->assertNull($counters[0]);
            $this->assertNull($counters[1]);

            $counters = $store->operations()
                ->send(new GetCountersOperation("users/2-A", ["votes"]))
                ->getCounters();

            $this->assertCount(1, $counters);
            $this->assertNull($counters[0]);
        } finally {
            $store->close();
        }
    }

    public function testSessionGetCounters(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Aviv1");

                $user2 = new User();
                $user2->setName("Aviv2");

                $session->store($user1, "users/1-A");
                $session->store($user2, "users/2-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("downloads", 500);
                $session->countersFor("users/2-A")->increment("votes", 1000);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $dic = $session->countersFor("users/1-A")->getAll();

                $this->assertCount(2, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(500, $dic['downloads']);

                $this->assertEquals(1000, $session->countersFor("users/2-A")->get("votes"));
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $dic = $session->countersFor("users/1-A")->get(["likes", "downloads"]);

                $this->assertCount(2, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(500, $dic['downloads']);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $dic = $session->countersFor("users/1-A")->get(["likes"]);

                $this->assertCount(1, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionGetCountersWithNonDefaultDatabase(): void
    {
        $store = $this->getDocumentStore();
        try {
            $dbName = "db-" . substr(UUID::uuid4(), 0, 10);

            $store->maintenance()->server()->send(new CreateDatabaseOperation(new DatabaseRecord($dbName)));

            try {
                $session = $store->openSession($dbName);
                try {
                    $user1 = new User();
                    $user1->setName("Aviv1");

                    $user2 = new User();
                    $user2->setName("Aviv2");

                    $session->store($user1, "users/1-A");
                    $session->store($user2, "users/2-A");
                    $session->saveChanges();
                } finally {
                    $session->close();
                }

                $session = $store->openSession($dbName);
                try {
                    $session->countersFor("users/1-A")->increment("likes", 100);
                    $session->countersFor("users/1-A")->increment("downloads", 500);
                    $session->countersFor("users/2-A")->increment("votes", 1000);

                    $session->saveChanges();
                } finally {
                    $session->close();
                }

                $session = $store->openSession($dbName);
                try {
                    $dic = $session->countersFor("users/1-A")->getAll();

                    $this->assertCount(2, $dic);

                    $this->assertArrayHasKey('likes', $dic);
                    $this->assertEquals(100, $dic['likes']);

                    $this->assertArrayHasKey('downloads', $dic);
                    $this->assertEquals(500, $dic['downloads']);

                    $x = $session->countersFor("users/2-A")->getAll();

                    $val = $session->countersFor("users/2-A")->get("votes");
                    $this->assertEquals(1000, $val);
                } finally {
                    $session->close();
                }
            } finally {
                $store->maintenance()->server()->send(new DeleteDatabasesOperation($dbName, true));
            }
        } finally {
            $store->close();
        }
    }

    public function testGetCountersFor(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Aviv1");
                $session->store($user1, "users/1-A");

                $user2 = new User();
                $user2->setName("Aviv2");
                $session->store($user2, "users/2-A");

                $user3 = new User();
                $user3->setName("Aviv3");
                $session->store($user3, "users/3-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("downloads", 100);
                $session->countersFor("users/2-A")->increment("votes", 1000);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $counters = $session->advanced()->getCountersFor($user);

                $this->assertCount(2, $counters);
                $this->assertContains('likes', $counters);
                $this->assertContains('downloads', $counters);

                $user = $session->load(User::class, "users/2-A");
                $counters = $session->advanced()->getCountersFor($user);

                $this->assertCount(1, $counters);
                $this->assertContains('votes', $counters);

                $user = $session->load(User::class, "users/3-A");
                $counters = $session->advanced()->getCountersFor($user);
                $this->assertNull($counters);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testDifferentTypesOfCountersOperationsInOneSession(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Aviv1");
                $session->store($user1, "users/1-A");

                $user2 = new User();
                $user2->setName("Aviv2");
                $session->store($user2, "users/2-A");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("downloads", 100);
                $session->countersFor("users/2-A")->increment("votes", 1000);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->delete("downloads");
                $session->countersFor("users/2-A")->increment("votes", -600);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->countersFor("users/1-A")->get("likes");
                $this->assertEquals(200, $val);

                $val = $session->countersFor("users/1-A")->get("downloads");
                $this->assertNull($val);

                $val = $session->countersFor("users/2-A")->get("votes");
                $this->assertEquals(400, $val);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestShouldThrow(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $session->countersFor($user)->increment("likes", 100);
                $session->saveChanges();

                $this->assertEquals(100, $session->countersFor($user)->get("likes"));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionShouldTrackCounters(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $this->assertEquals(0, $session->advanced()->getNumberOfRequests());

                $val = $session->countersFor("users/1-A")->get("likes");
                $this->assertEquals(100, $val);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $session->countersFor("users/1-A")->get("likes");
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionShouldKeepNullsInCountersCache(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("dislikes", 200);
                $session->countersFor("users/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->countersFor("users/1-A")->get("score");
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertNull($val);

                $val = $session->countersFor("users/1-A")->get("score");
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertNull($val);

                $dic = $session->countersFor("users/1-A")->getAll();
                //should not contain null value for "score"

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionShouldKnowWhenItHasAllCountersInCacheAndAvoidTripToServer_WhenUsingEntity(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("dislikes", 200);
                $session->countersFor("users/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $userCounters = $session->countersFor($user);

                $val = $userCounters->get("likes");
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertEquals(100, $val);

                $val = $userCounters->get("dislikes");
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
                $this->assertEquals(200, $val);

                $val = $userCounters->get("downloads");
                // session should know at this point that it has all counters

                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());
                $this->assertEquals(300, $val);

                $dic = $userCounters->getAll(); // should not go to server
                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $val = $userCounters->get("score"); //should not go to server
                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());
                $this->assertNull($val);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionShouldUpdateMissingCountersInCacheAndRemoveDeletedCounters_AfterRefresh(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("dislikes", 200);
                $session->countersFor("users/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $userCounters = $session->countersFor($user);
                $dic = $userCounters->getAll();
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $session2 = $store->openSession();
                try {
                    $session2->countersFor("users/1-A")->increment("likes");
                    $session2->countersFor("users/1-A")->delete("dislikes");
                    $session2->countersFor("users/1-A")->increment("score", 1000); // new counter
                    $session2->saveChanges();
                } finally {
                    $session2->close();
                }

                $session->advanced()->refresh($user);

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                // Refresh updated the document in session,
                // cache should know that it's missing 'score' by looking
                // at the document's metadata and go to server again to get all.
                // this should override the cache entirely and therefor
                // 'dislikes' won't be in cache anymore

                $dic = $userCounters->getAll();
                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(101, $dic['likes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $this->assertArrayHasKey('score', $dic);
                $this->assertEquals(1000, $dic['score']);

                // cache should know that it got all and not go to server,
                // and it shouldn't have 'dislikes' entry anymore
                $val = $userCounters->get("dislikes");
                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());
                $this->assertNull($val);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionShouldUpdateMissingCountersInCacheAndRemoveDeletedCounters_AfterLoadFromServer(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("dislikes", 200);
                $session->countersFor("users/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {

                $userCounters = $session->countersFor("users/1-A");
                $dic = $userCounters->getAll();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $session2 = $store->openSession();
                try {
                    $session2->countersFor("users/1-A")->increment("likes");
                    $session2->countersFor("users/1-A")->delete("dislikes");
                    $session2->countersFor("users/1-A")->increment("score", 1000); // new counter
                    $session2->saveChanges();
                } finally {
                    $session2->close();
                }

                $user = $session->load(User::class, "users/1-A");

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                // Refresh updated the document in session,
                // cache should know that it's missing 'score' by looking
                // at the document's metadata and go to server again to get all.
                // this should override the cache entirely and therefor
                // 'dislikes' won't be in cache anymore

                $dic = $userCounters->getAll();
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(101, $dic['likes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $this->assertArrayHasKey('score', $dic);
                $this->assertEquals(1000, $dic['score']);

                // cache should know that it got all and not go to server,
                // and it shouldn't have 'dislikes' entry anymore
                $val = $userCounters->get("dislikes");
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
                $this->assertNull($val);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionClearShouldClearCountersCache(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("dislikes", 200);
                $session->countersFor("users/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $userCounters = $session->countersFor("users/1-A");
                $dic = $userCounters->getAll();

                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $session2 = $store->openSession();
                try {
                    $session2->countersFor("users/1-A")->increment("likes");
                    $session2->countersFor("users/1-A")->delete("dislikes");
                    $session2->countersFor("users/1-A")->increment("score", 1000); // new counter
                    $session2->saveChanges();
                } finally {
                    $session2->close();
                }

                $session->advanced()->clear(); // should clear countersCache

                $dic = $userCounters->getAll(); // should go to server again
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(101, $dic['likes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $this->assertArrayHasKey('score', $dic);
                $this->assertEquals(1000, $dic['score']);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionEvictShouldRemoveEntryFromCountersCache(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("dislikes", 200);
                $session->countersFor("users/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $userCounters = $session->countersFor("users/1-A");
                $dic = $userCounters->getAll();
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $session2 = $store->openSession();
                try {
                    $session2->countersFor("users/1-A")->increment("likes");
                    $session2->countersFor("users/1-A")->delete("dislikes");
                    $session2->countersFor("users/1-A")->increment("score", 1000); // new counter
                    $session2->saveChanges();
                } finally {
                    $session2->close();
                }

                $session->advanced()->evict($user);  // should remove 'users/1-A' entry from  CountersByDocId

                $dic = $userCounters->getAll(); // should go to server again
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(101, $dic['likes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $this->assertArrayHasKey('score', $dic);
                $this->assertEquals(1000, $dic['score']);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionShouldAlwaysLoadCountersFromCacheAfterGetAll(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("dislikes", 200);
                $session->countersFor("users/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $dic = $session->countersFor("users/1-A")->getAll();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);


                //should not go to server after GetAll() request
                $val = $session->countersFor("users/1-A")->get("likes");
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertEquals(100, $val);

                $val = $session->countersFor("users/1-A")->get("votes");
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertNull($val);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionShouldOverrideExistingCounterValuesInCacheAfterGetAll(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("dislikes", 200);
                $session->countersFor("users/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->countersFor("users/1-A")->get("likes");
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertEquals(100, $val);

                $val = $session->countersFor("users/1-A")->get("score");
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertNull($val);

                $operation = new DocumentCountersOperation();
                $operation->setDocumentId("users/1-A");
                $operation->setOperations([CounterOperation::create("likes", CounterOperationType::increment(), 400)]);

                $counterBatch = new CounterBatch();
                $counterBatch->setDocuments([$operation]);

                $store->operations()->send(new CounterBatchOperation($counterBatch));

                $dic = $session->countersFor("users/1-A")->getAll();
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(500, $dic['likes']);


                $val = $session->countersFor("users/1-A")->get("score");
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
                $this->assertNull($val);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestSessionIncrementCounterShouldUpdateCounterValueAfterSaveChanges(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("dislikes", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->countersFor("users/1-A")->get("likes");
                $this->assertEquals(100, $val);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $session->countersFor("users/1-A")->increment("likes", 50);  // should not increment the counter value in cache
                $val = $session->countersFor("users/1-A")->get("likes");
                $this->assertEquals(100, $val);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $session->countersFor("users/1-A")->increment("dislikes", 200);  // should not add the counter to cache
                $val = $session->countersFor("users/1-A")->get("dislikes"); // should go to server
                $this->assertEquals(300, $val);
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $session->countersFor("users/1-A")->increment("score", 1000);  // should not add the counter to cache
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                // SaveChanges should updated counters values in cache
                // according to increment result
                $session->saveChanges();
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                // should not go to server for these
                $val = $session->countersFor("users/1-A")->get("likes");
                $this->assertEquals(150, $val);

                $val = $session->countersFor("users/1-A")->get("dislikes");
                $this->assertEquals(500, $val);

                $val = $session->countersFor("users/1-A")->get("score");
                $this->assertEquals(1000, $val);

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestSessionShouldRemoveCounterFromCacheAfterCounterDeletion(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->countersFor("users/1-A")->get("likes");
                $this->assertEquals(100, $val);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $session->countersFor("users/1-A")->delete("likes");
                $session->saveChanges();
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $val = $session->countersFor("users/1-A")->get("likes");
                $this->assertNull($val);

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestSessionShouldRemoveCountersFromCacheAfterDocumentDeletion(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("dislikes", 200);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $dic = $session->countersFor("users/1-A")->get(["likes", "dislikes"]);

                $this->assertCount(2, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $session->delete("users/1-A");
                $session->saveChanges();

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $val = $session->countersFor("users/1-A")->get("likes");
                $this->assertNull($val);

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestSessionIncludeSingleCounter(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");

                $session->countersFor("users/1-A")->increment("likes", 100);
                $session->countersFor("users/1-A")->increment("dislikes", 200);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A", function ($i) {
                    $i->includeCounter("likes");
                });
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $counter = $session->countersFor($user)->get("likes");
                $this->assertEquals(100, $counter);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestSessionChainedIncludeCounter(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1-A");

                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");

                $session->countersFor("orders/1-A")->increment("likes", 100);
                $session->countersFor("orders/1-A")->increment("dislikes", 200);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $order = $session->load(Order::class, "orders/1-A", function ($i) {
                    $i->includeCounter("likes")->includeCounter("dislikes");
                });

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $counter = $session->countersFor($order)->get("likes");
                $this->assertEquals(100, $counter);

                $counter = $session->countersFor($order)->get("dislikes");
                $this->assertEquals(200, $counter);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestSessionChainedIncludeAndIncludeCounter(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1-A");

                $employee = new Employee();
                $employee->setFirstName("Aviv");
                $session->store($employee, "employees/1-A");

                $order = new Order();
                $order->setCompany("companies/1-A");
                $order->setEmployee("employees/1-A");
                $session->store($order, "orders/1-A");

                $session->countersFor("orders/1-A")->increment("likes", 100);
                $session->countersFor("orders/1-A")->increment("dislikes", 200);
                $session->countersFor("orders/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $order = $session->load(Order::class, "orders/1-A",
                    function ($i) {
                        $i->includeCounter("likes")
                            ->includeDocuments("company")
                            ->includeCounter("dislikes")
                            ->includeCounter("downloads")
                            ->includeDocuments("employee");
                    });

                $company = $session->load(Company::class, $order->getCompany());
                $this->assertEquals("HR", $company->getName());

                $employee = $session->load(Employee::class, $order->getEmployee());
                $this->assertEquals("Aviv", $employee->getFirstName());

                $dic = $session->countersFor($order)->getAll();
                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestSessionIncludeCounters(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1-A");

                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");

                $session->countersFor("orders/1-A")->increment("likes", 100);
                $session->countersFor("orders/1-A")->increment("dislikes", 200);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $order = $session->load(Order::class, "orders/1-A",
                    function ($i) {
                        $i->includeDocuments("company")
                            ->includeCounters(["likes", "dislikes"]);
                    });

                $company = $session->load(Company::class, $order->getCompany());
                $this->assertEquals("HR", $company->getName());

                $dic = $session->countersFor($order)->getAll();
                $this->assertCount(2, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestSessionIncludeAllCounters(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1-A");

                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");

                $session->countersFor("orders/1-A")->increment("likes", 100);
                $session->countersFor("orders/1-A")->increment("dislikes", 200);
                $session->countersFor("orders/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $order = $session->load(Order::class, "orders/1-A",
                    function ($i) {
                        $i->includeDocuments("company")->includeAllCounters();
                    }
                );

                $company = $session->load(Company::class, $order->getCompany());
                $this->assertEquals("HR", $company->getName());

                $dic = $session->countersFor($order)->getAll();
                $this->assertCount(3, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestSessionIncludeSingleCounterAfterIncludeAllCountersShouldThrow(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1-A");

                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");

                $session->countersFor("orders/1-A")->increment("likes", 100);
                $session->countersFor("orders/1-A")->increment("dislikes", 200);
                $session->countersFor("orders/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                try {
                    $session->load(Order::class, "orders/1-A",
                        function ($i) {
                            $i->includeDocuments("company")
                                ->includeAllCounters()
                                ->includeCounter("likes");
                        }
                    );

                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IllegalStateException::class, $exception);
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionIncludeAllCountersAfterIncludeSingleCounterShouldThrow(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1-A");

                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");

                $session->countersFor("orders/1-A")->increment("likes", 100);
                $session->countersFor("orders/1-A")->increment("dislikes", 200);
                $session->countersFor("orders/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                try {
                    $session->load(Order::class, "orders/1-A",
                        function ($i) {
                            $i->includeDocuments("company")
                                ->includeCounter("likes")
                                ->includeAllCounters();
                        });

                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IllegalStateException::class, $exception);
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestSessionIncludeCountersShouldRegisterMissingCounters(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1-A");

                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");

                $session->countersFor("orders/1-A")->increment("likes", 100);
                $session->countersFor("orders/1-A")->increment("dislikes", 200);
                $session->countersFor("orders/1-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $order = $session->load(Order::class, "orders/1-A",
                    function ($i) {
                        $i
                            ->includeDocuments("company")
                            ->includeCounters(["likes", "downloads", "dances"])
                            ->includeCounter("dislikes")
                            ->includeCounter("cats");
                    });

                $company = $session->load(Company::class, $order->getCompany());
                $this->assertEquals("HR", $company->getName());

                // should not go to server
                $dic = $session->countersFor($order)->getAll();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertCount(5, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(300, $dic['downloads']);

                //missing counters should be in cache
                $this->assertNull($dic["dances"]);
                $this->assertNull($dic["cats"]);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function atestSessionIncludeCountersMultipleLoads(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company1 = new Company();
                $company1->setName("HR");
                $session->store($company1, "companies/1-A");

                $order1 = new Order();
                $order1->setCompany("companies/1-A");
                $session->store($order1, "orders/1-A");

                $company2 = new Company();
                $company2->setName("HP");
                $session->store($company2, "companies/2-A");

                $order2 = new Order();
                $order2->setCompany("companies/2-A");
                $session->store($order2, "orders/2-A");

                $session->countersFor("orders/1-A")->increment("likes", 100);
                $session->countersFor("orders/1-A")->increment("dislikes", 200);

                $session->countersFor("orders/2-A")->increment("score", 300);
                $session->countersFor("orders/2-A")->increment("downloads", 400);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $orders = $session->load(Order::class, [ "orders/1-A", "orders/2-A" ], function($i) { $i->includeDocuments("company")->includeAllCounters(); });

                $order = $orders["orders/1-A"];
                $company = $session->load(Company::class, $order->getCompany());
                $this->assertEquals("HR", $company->getName());

                $dic = $session->countersFor($order)->getAll();
                $this->assertCount(2, $dic);

                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);

                $this->assertArrayHasKey('dislikes', $dic);
                $this->assertEquals(200, $dic['dislikes']);

                $order = $orders["orders/2-A"];
                $company = $session->load(Company::class, $order->getCompany());
                $this->assertEquals("HP", $company->getName());

                $dic = $session->countersFor($order)->getAll();
                $this->assertCount(2, $dic);

                $this->assertArrayHasKey('score', $dic);
                $this->assertEquals(300, $dic['score']);

                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(400, $dic['downloads']);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
