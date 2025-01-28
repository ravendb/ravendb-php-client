<?php

namespace tests\RavenDB\Test\Client\_QueryTest;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\CollectionStatistics;
use RavenDB\Documents\Operations\GetCollectionStatisticsOperation;
use RavenDB\Documents\Queries\Query;
use RavenDB\Documents\Queries\SearchOperator;
use RavenDB\Documents\Session\BeforeQueryEventArgs;
use RavenDB\Documents\Session\DocumentQueryInterface;
use RavenDB\Documents\Session\DocumentSession;
use RavenDB\Documents\Session\DocumentSessionInterface;
use RavenDB\Documents\Session\GroupByField;
use RavenDB\Documents\Session\OrderingType;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Type\Collection;
use RavenDB\Type\Duration;
use RavenDB\Utils\DateUtils;
use RavenDB\Utils\StringUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\Infrastructure\Entity\Order;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\_QueryTest\Entity\Article;
use tests\RavenDB\Test\Client\_QueryTest\Entity\Dog;
use tests\RavenDB\Test\Client\_QueryTest\Entity\ReduceResult;
use tests\RavenDB\Test\Client\_QueryTest\Entity\UserProjection;
use tests\RavenDB\Test\Client\_QueryTest\Index\DogsIndex;
use tests\RavenDB\Test\Client\_QueryTest\Index\DogsIndexResult;
use tests\RavenDB\Test\Client\_QueryTest\Index\OrderTime;
use tests\RavenDB\Test\Client\_QueryTest\Index\UsersByName;

class QueryTest extends RemoteTestBase
{
    public function test_Query_CreateClausesForQueryDynamicallyWithOnBeforeQueryEvent()
    {
        $store = $this->getDocumentStore();

        try {
            $id1 = 'users/1';
            $id2 = 'users/2';

            $session = $store->openSession();

            try {
                $article1 = new Article();
                $article1->setTitle("foo");
                $article1->setDescription("bar");
                $article1->setDeleted(false);
                $session->store($article1, $id1);

                $article2 = new Article();
                $article2->setTitle("foo");
                $article2->setDescription("bar");
                $article2->setDeleted(true);
                $session->store($article2, $id2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->addBeforeQueryListener(function ($sender, BeforeQueryEventArgs $event) {
                    /** @var DocumentQueryInterface $queryToBeExecuted */
                    $queryToBeExecuted = $event->getQueryCustomization()->getQuery();
                    $queryToBeExecuted->andAlso(true);
                    $queryToBeExecuted->whereEquals("deleted", true);
                });

                $query = $session->query(Article::class)
                    ->search('title', 'foo')
                    ->search('description', 'bar', SearchOperator::or());

                $result = $query->toList();

                $this->assertEquals(
                    "from 'Articles' where (search(title, \$p0) or search(description, \$p1)) and deleted = \$p2",
                    $query->toString()
                );

                $this->assertCount(1, $result);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQuery_CreateClausesForQueryDynamicallyWhenTheQueryEmpty(): void
    {
        $store = $this->getDocumentStore();
        try {
            $id1 = "users/1";
            $id2 = "users/2";

            $session = $store->openSession();
            try {
                $article1 = new Article();
                $article1->setTitle("foo");
                $article1->setDescription("bar");
                $article1->setDeleted(false);
                $session->store($article1, $id1);

                $article2 = new Article();
                $article2->setTitle("foo");
                $article2->setDescription("bar");
                $article2->setDeleted(true);
                $session->store($article2, $id2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->advanced()->documentQuery(Article::class)
                    ->andAlso(true);

                $this->assertEquals("from 'Articles'", $query->toString());

                $queryResult = $query->toList();
                $this->assertCount(2, $queryResult);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQuerySimple(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {

                $user1 = new User();
                $user1->setName("John");

                $user2 = new User();
                $user2->setName("Jane");

                $user3 = new User();
                $user3->setName("Tarzan");

                $session->store($user1, "users/1");
                $session->store($user2, "users/2");
                $session->store($user3, "users/3");
                $session->saveChanges();

                $queryResult = $session
                    ->advanced()
                    ->documentQuery(User::class, null, "users", false)
                    ->toList();

                $this->assertCount(3, $queryResult);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryLazily(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {

                $user1 = new User();
                $user1->setName("John");

                $user2 = new User();
                $user2->setName("Jane");

                $user3 = new User();
                $user3->setName("Tarzan");

                $session->store($user1, "users/1");
                $session->store($user2, "users/2");
                $session->store($user3, "users/3");
                $session->saveChanges();

                $lazyQuery = $session->query(User::class)
                        ->lazily();

                $queryResult = $lazyQuery->getValue();

                $this->assertCount(3, $queryResult);

                $this->assertEquals("John", $queryResult[0]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCollectionsStats(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();

            try {
                $user1 = new User();
                $user1->setName("John");

                $user2 = new User();
                $user2->setName("Jane");

                $session->store($user1, "users/1");
                $session->store($user2, "users/2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var CollectionStatistics $stats */
            $stats = $store->maintenance()->send(new GetCollectionStatisticsOperation());

            $this->assertEquals(2, $stats->getCountOfDocuments());

            $this->assertEquals(2, $stats->getCollections()["Users"]);
        } finally {
            $store->close();
        }
    }

    public function testQueryWithWhereClause(): void
    {
        $store = $this->getDocumentStore();

        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("John");

                $user2 = new User();
                $user2->setName("Jane");

                $user3 = new User();
                $user3->setName("Tarzan");

                $session->store($user1, "users/1");
                $session->store($user2, "users/2");
                $session->store($user3, "users/3");
                $session->saveChanges();

                $queryResult = $session->query(User::class, Query::collection("users"))
                    ->whereStartsWith("name", "J")
                    ->toList();

                $queryResult2 = $session->query(User::class, Query::collection("users"))
                    ->whereEquals("name", "Tarzan")
                    ->toList();

                $queryResult3 = $session->query(User::class, Query::collection("users"))
                    ->whereEndsWith("name", "n")
                    ->toList();

                $this->assertCount(2, $queryResult);

                $this->assertCount(1, $queryResult2);

                $this->assertCount(2, $queryResult3);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryMapReduceWithCount(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $results = $session->query(User::class)
                    ->groupBy("name")
                    ->selectKey()
                    ->selectCount()
                    ->orderByDescending("count")
                    ->ofType(ReduceResult::class)
                    ->toList();

                $this->assertEquals(2, $results[0]->getCount());
                $this->assertEquals("John", $results[0]->getName());

                $this->assertEquals(1, $results[1]->getCount());
                $this->assertEquals("Tarzan", $results[1]->getName());
            } finally {
                $session->close();
            }

        } finally {
            $store->close();
        }

    }

    public function testQueryMapReduceWithSum(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {


                $results = $session->query(User::class)
                    ->groupBy("name")
                    ->selectKey()
                    ->selectSum(new GroupByField("age"))
                    ->orderByDescending("age")
                    ->ofType(ReduceResult::class)
                    ->toList();

                $this->assertEquals(8, $results[0]->getAge());
                $this->assertEquals("John", $results[0]->getName());

                $this->assertEquals(2, $results[1]->getAge());
                $this->assertEquals("Tarzan", $results[1]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryMapReduceIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {

                $results = $session->query(ReduceResult::class, Query::index("UsersByName"))
                    ->orderByDescending("count")
                    ->toList();

                $this->assertEquals(2, $results[0]->getCount());
                $this->assertEquals("John", $results[0]->getName());

                $this->assertEquals(1, $results[1]->getCount());
                $this->assertEquals("Tarzan", $results[1]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQuerySingleProperty(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $ages = $session->query(User::class)
                    ->addOrder("age", true, OrderingType::long())
                    ->selectFields(null, "age")
                    ->toList();

                $this->assertCount(3, $ages);
                $this->assertEquals([5, 3, 2], $ages);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWithSelect(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $usersAge = $session->query(User::class)
                    ->selectFields(User::class, "age", "id")
                    ->toList();

                /** @var User $user */
                foreach ($usersAge as $user) {
                    $this->assertGreaterThan(0, $user->getAge());
                    $this->assertNotNull($user->getId());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWithWhereIn(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {

                $users = $session->query(User::class)
                    ->whereIn("name", new Collection(["Tarzan", "no_such"]))
                    ->toList();

                $this->assertCount(1, $users);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWithWhereBetween(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {

                $users = $session->query(User::class)
                    ->whereBetween("age", 4, 5)
                    ->toList();

                $this->assertCount(1, $users);

                $this->assertEquals("John", $users[0]->getName());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWithWhereLessThan(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $users = $session->query(User::class)
                    ->whereLessThan("age", 3)
                    ->toList();

                $this->assertCount(1, $users);

                $this->assertEquals("Tarzan", $users[0]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWithWhereLessThanOrEqual(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $users = $session->query(User::class)
                    ->whereLessThanOrEqual("age", 3)
                    ->toList();

                $this->assertCount(2, $users);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWithWhereGreaterThan(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {

                $users = $session->query(User::class)
                    ->whereGreaterThan("age", 3)
                    ->toList();

                $this->assertCount(1, $users);

                $this->assertEquals("John", $users[0]->getName());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();;
        }
    }

    public function testQueryWithWhereGreaterThanOrEqual(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {

                $users = $session->query(User::class)
                    ->whereGreaterThanOrEqual("age", 3)
                    ->toList();

                $this->assertCount(2, $users);
            } finally {
                $session->close();
            }

        } finally {
            $store->close();
        }
    }

    public function testQueryWithProjection(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {

                $projections = $session->query(User::class)
                    ->selectFields(UserProjection::class)
                    ->toList();

                $this->assertCount(3, $projections);

                /** @var UserProjection $projection */
                foreach ($projections as $projection) {
                    $this->assertNotNull($projection->getId());
                    $this->assertNotNull($projection->getName());

                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWithProjection2(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $projections = $session->query(User::class)
                    ->selectFields(UserProjection::class, "lastName", "id")
                    ->toList();

                $this->assertCount(3, $projections);

                /** @var UserProjection $projection */
                foreach ($projections as $projection) {
                    $this->assertNotNull($projection->getId());
                    $this->assertNull($projection->getName());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryDistinct(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $uniqueNames = $session->query(User::class)
                    ->selectFields(null,"name")
                    ->distinct()
                    ->toList();

                $this->assertCount(2, $uniqueNames);
                $this->assertContains("Tarzan", $uniqueNames);
                $this->assertContains("John", $uniqueNames);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }


    public function testQuerySearchWithOr(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $uniqueNames = $session->query(User::class)
                    ->search("name", "Tarzan John", SearchOperator::or())
                    ->toList();

                $this->assertCount(3, $uniqueNames);
            } finally {
                $session->close();
            }

        } finally {
            $store->close();
        }
    }

    public function testQueryNoTracking(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            /** @var DocumentSession $session */
            $session = $store->openSession();
            try {
                $users = $session->query(User::class)
                    ->noTracking()
                    ->toList();

                $this->assertCount(3, $users);

                foreach ($users as $user) {
                    $this->assertFalse($session->isLoaded($user->getId()));
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }


    public function testQuerySkipTake(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $users = $session->query(User::class)
                    ->orderBy("name")
                    ->skip(2)
                    ->take(1)
                    ->toList();

                $this->assertCount(1, $users);

                $this->assertEquals("Tarzan", $users[0]->getName());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testRawQuerySkipTake(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $users = $session->rawQuery(User::class, "from users")
                    ->skip(2)
                    ->take(1)
                    ->toList();

                $this->assertCount(1, $users);

                $this->assertEquals("Tarzan", $users[0]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }


    public function testParametersInRawQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $users = $session->rawQuery(User::class, "from users where age == \$p0")
                    ->addParameter("p0", 5)
                    ->toList();

                $this->assertCount(1, $users);

                $this->assertEquals("John", $users[0]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryLucene(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $users = $session->query(User::class)
                    ->whereLucene("name", "Tarzan")
                    ->toList();

                $this->assertCount(1, $users);

                foreach ($users as $user) {
                    $this->assertEquals("Tarzan", $user->getName());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWhereExact(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $users = $session->query(User::class)
                    ->whereEquals("name", "tarzan")
                    ->toList();

                $this->assertCount(1, $users);

                $users = $session->query(User::class)
                    ->whereEquals("name", "tarzan", true)
                    ->toList();

                $this->assertCount(0, $users); // we queried for tarzan with exact

                $users = $session->query(User::class)
                    ->whereEquals("name", "Tarzan", true)
                    ->toList();

                $this->assertCount(1, $users); // we queried for Tarzan with exact
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWhereNot(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $this->assertCount(
                    2,
                    $session->query(User::class)
                        ->not()
                        ->whereEquals("name", "tarzan")
                        ->toList()
                );

                $this->assertCount(
                    2,
                    $session->query(User::class)
                        ->whereNotEquals("name", "tarzan")
                        ->toList()
                );

                $this->assertCount(
                    2,
                    $session->query(User::class)
                        ->whereNotEquals("name", "Tarzan", true)
                        ->toList()
                );
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWithDuration(): void
    {
        $store = $this->getDocumentStore();
        try {
            $now = DateUtils::now();

            $store->executeIndex(new OrderTime());

            $session = $store->openSession();
            try {
                $order1 = new Order();
                $order1->setCompany("hours");
                $order1->setOrderedAt(DateUtils::addHours($now, -2));
                $order1->setShippedAt($now);
                $session->store($order1);

                $order2 = new Order();
                $order2->setCompany("days");
                $order2->setOrderedAt(DateUtils::addDays($now, -2));
                $order2->setShippedAt($now);
                $session->store($order2);

                $order3 = new Order();
                $order3->setCompany("minutes");
                $order3->setOrderedAt(DateUtils::addMinutes($now, -2));
                $order3->setShippedAt($now);
                $session->store($order3);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $orders = $session->query(Order::class, 'OrderTime')
                    ->whereLessThan("delay", Duration::ofHours(3))
                    ->toList();
                $delay = array_map(function (Order $o) {
                    return $o->getCompany();
                }, $orders);

                $this->assertEquals(["hours", "minutes"], $delay);

                $orders = $session->query(Order::class, 'OrderTime')
                    ->whereGreaterThan("delay", Duration::ofHours(3))
                    ->toList();

                $delay = array_map(function (Order $o) {
                    return $o->getCompany();
                }, $orders);

                $this->assertEquals(["days"], $delay);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryFirst(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();

            try {
                $first = $session->query(User::class)
                    ->first();

                $this->assertNotNull($first);

                $this->assertNotNull(
                    $session->query(User::class)
                        ->whereEquals("name", "Tarzan")
                        ->single()
                );

                $this->assertNotNull($first);

                $this->expectException(IllegalStateException::class);
                $session->query(User::class)
                    ->single();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }

    }

    public function testQueryParameters(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {

                $count = $session->rawQuery(User::class, "from Users where name = \$name")
                    ->addParameter("name", "Tarzan")
                    ->count();
                $this->assertEquals(1, $count);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryRandomOrder(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {

                $this->assertCount(3, $session->query(User::class)
                    ->randomOrdering()
                    ->toList());

                $this->assertCount(3, $session->query(User::class)
                    ->randomOrdering("123")
                    ->toList());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWhereExists(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $users = $session->query(User::class)
                    ->whereExists("name")
                    ->toList();


                $users2 = $session->query(User::class)
                    ->whereExists("name")
                    ->andAlso()
                    ->not()
                    ->whereExists("no_such_field")
                    ->toList();

                $this->assertcount(3, $users2);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryWithBoost(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            $this->addUsers($store);

            $session = $store->openSession();
            try {
                $users = $session->query(User::class)
                    ->whereEquals("name", "Tarzan")
                    ->boost(5)
                    ->orElse()
                    ->whereEquals("name", "John")
                    ->boost(2)
                    ->orderByScore()
                    ->toList();

                $this->assertCount(3, $users);

                $names = array_map(function (User $o) {
                    return $o->getName();
                }, $users);

                $this->assertSame(["Tarzan", "John", "John"], $names);

                $users = $session->query(User::class)
                    ->whereEquals("name", "Tarzan")
                    ->boost(2)
                    ->orElse()
                    ->whereEquals("name", "John")
                    ->boost(5)
                    ->orderByScore()
                    ->toList();

                $this->assertCount(3, $users);

                $names = array_map(function (User $o) {
                    return $o->getName();
                }, $users);

                $this->assertSame(["John", "John", "Tarzan"], $names);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    private function addUsers(DocumentStoreInterface $store): void
    {
        $session = $store->openSession();
        try {
            $user1 = new User();
            $user1->setName("John");
            $user1->setAge(3);

            $user2 = new User();
            $user2->setName("John");
            $user2->setAge(5);

            $user3 = new User();
            $user3->setName("Tarzan");
            $user3->setAge(2);

            $session->store($user1, "users/1");
            $session->store($user2, "users/2");
            $session->store($user3, "users/3");
            $session->saveChanges();
        } finally {
            $session->close();
        }

        $store->executeIndex(new UsersByName());
        $this->waitForIndexing($store);
    }

    public function testQueryWithCustomize(): void
    {
        $store = $this->getDocumentStore();
        try {
            $dogsIndex = new DogsIndex();
            $dogsIndex->execute($store);

            $newSession = $store->openSession();
            try {
                $this->createDogs($newSession);

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {

                $dogsIndex = new DogsIndex();
                $queryResult = $newSession->advanced()
                    ->documentQuery(DogsIndexResult::class, $dogsIndex->getIndexName(), null, false)
                    ->waitForNonStaleResults(null)
                    ->orderBy("name", OrderingType::alphaNumeric())
                    ->whereGreaterThan("age", 2)
                    ->toList();

                $this->assertCount(4, $queryResult);

                $this->assertEquals("Brian", $queryResult[0]->getName());

                $this->assertEquals("Django", $queryResult[1]->getName());

                $this->assertEquals("Lassie", $queryResult[2]->getName());

                $this->assertEquals("Snoopy", $queryResult[3]->getName());
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    private function createDogs(DocumentSessionInterface $newSession): void
    {
        $dog1 = new Dog();
        $dog1->setName("Snoopy");
        $dog1->setBreed("Beagle");
        $dog1->setColor("White");
        $dog1->setAge(6);
        $dog1->setVaccinated(true);

        $newSession->store($dog1, "docs/1");

        $dog2 = new Dog();
        $dog2->setName("Brian");
        $dog2->setBreed("Labrador");
        $dog2->setColor("White");
        $dog2->setAge(12);
        $dog2->setVaccinated(false);

        $newSession->store($dog2, "docs/2");

        $dog3 = new Dog();
        $dog3->setName("Django");
        $dog3->setBreed("Jack Russel");
        $dog3->setColor("Black");
        $dog3->setAge(3);
        $dog3->setVaccinated(true);

        $newSession->store($dog3, "docs/3");

        $dog4 = new Dog();
        $dog4->setName("Beethoven");
        $dog4->setBreed("St. Bernard");
        $dog4->setColor("Brown");
        $dog4->setAge(1);
        $dog4->setVaccinated(false);

        $newSession->store($dog4, "docs/4");

        $dog5 = new Dog();
        $dog5->setName("Scooby Doo");
        $dog5->setBreed("Great Dane");
        $dog5->setColor("Brown");
        $dog5->setAge(0);
        $dog5->setVaccinated(false);

        $newSession->store($dog5, "docs/5");

        $dog6 = new Dog();
        $dog6->setName("Old Yeller");
        $dog6->setBreed("Black Mouth Cur");
        $dog6->setColor("White");
        $dog6->setAge(2);
        $dog6->setVaccinated(true);

        $newSession->store($dog6, "docs/6");

        $dog7 = new Dog();
        $dog7->setName("Benji");
        $dog7->setBreed("Mixed");
        $dog7->setColor("White");
        $dog7->setAge(0);
        $dog7->setVaccinated(false);

        $newSession->store($dog7, "docs/7");

        $dog8 = new Dog();
        $dog8->setName("Lassie");
        $dog8->setBreed("Collie");
        $dog8->setColor("Brown");
        $dog8->setAge(6);
        $dog8->setVaccinated(true);

        $newSession->store($dog8, "docs/8");
    }

    public function testQueryLongRequest(): void
    {
        $store = $this->getDocumentStore();
        try {
            $newSession = $store->openSession();
            try {
                $longName = StringUtils::repeat('x', 2048);
                $user = new User();
                $user->setName($longName);
                $newSession->store($user, "users/1");

                $newSession->saveChanges();

                $queryResult = $newSession
                    ->advanced()
                    ->documentQuery(User::class, null, "Users", false)
                    ->whereEquals("name", $longName)
                    ->toList();

                $this->assertCount(1, $queryResult);
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testQueryByIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $dogsIndex = new DogsIndex();
            $dogsIndex->execute($store);

            $session = $store->openSession();
            try {
                $this->createDogs($session);

                $session->saveChanges();

                self::waitForIndexing($store, $store->getDatabase(), null);
            } finally {
                $session->close();
            }

            $newSession = $store->openSession();
            try {
                $dogsIndex = new DogsIndex();
                $queryResult = $newSession->advanced()
                    ->documentQuery(DogsIndexResult::class, $dogsIndex->getIndexName(), null, false)
                    ->whereGreaterThan("age", 2)
                    ->andAlso()
                    ->whereEquals("vaccinated", false)
                    ->toList();

                $this->assertCount(1, $queryResult);

                $this->assertEquals("Brian", $queryResult[0]->getName());


                $queryResult2 = $newSession->advanced()
                    ->documentQuery(DogsIndexResult::class, $dogsIndex->getIndexName(), null, false)
                    ->whereLessThanOrEqual("age", 2)
                    ->andAlso()
                    ->whereEquals("vaccinated", false)
                    ->toList();

                $this->assertCount(3, $queryResult2);

                $list = array_map(function (DogsIndexResult $d) {
                    return $d->getName();
                }, $queryResult2);


                $this->assertContains("Beethoven", $list);
                $this->assertContains("Scooby Doo", $list);
                $this->assertContains("Benji", $list);
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }
}
