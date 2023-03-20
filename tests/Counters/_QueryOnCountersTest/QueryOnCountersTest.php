<?php

namespace tests\RavenDB\Counters\_QueryOnCountersTest;

use Closure;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\Employee;
use tests\RavenDB\Infrastructure\Entity\Order;
use tests\RavenDB\RemoteTestBase;

class QueryOnCountersTest extends RemoteTestBase
{
    public function testRawQuerySelectSingleCounter(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Jerry");

                $user2 = new User();
                $user2->setName("Bob");

                $user3 = new User();
                $user3->setName("Pigpen");

                $session->store($user1, "users/1-A");
                $session->store($user2, "users/2-A");
                $session->store($user3, "users/3-A");

                $session->countersFor("users/1-A")->increment("Downloads", 100);
                $session->countersFor("users/2-A")->increment("Downloads", 200);
                $session->countersFor("users/3-A")->increment("Likes", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session
                        ->advanced()
                        ->rawQuery(CounterResult::class, "from users select counter(\"Downloads\") as downloads")
                        ->toList();

                $this->assertCount(3, $query);

                $this->assertEquals(100, $query[0]->getDownloads());
                $this->assertEquals(200, $query[1]->getDownloads());
                $this->assertNull($query[2]->getDownloads());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testRawQuerySelectSingleCounterWithDocAlias(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Jerry");

                $user2 = new User();
                $user2->setName("Bob");

                $user3 = new User();
                $user3->setName("Pigpen");

                $session->store($user1, "users/1-A");
                $session->store($user2, "users/2-A");
                $session->store($user3, "users/3-A");

                $session->countersFor("users/1-A")->increment("Downloads", 100);
                $session->countersFor("users/2-A")->increment("Downloads", 200);
                $session->countersFor("users/3-A")->increment("Likes", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session
                        ->advanced()
                        ->rawQuery(CounterResult::class, "from users as u select counter(u, \"Downloads\") as downloads")
                        ->toList();

                $this->assertCount(3, $query);

                $this->assertEquals(100, $query[0]->getDownloads());
                $this->assertEquals(200, $query[1]->getDownloads());
                $this->assertNull($query[2]->getDownloads());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testRawQuerySelectMultipleCounters(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Jerry");

                $user2 = new User();
                $user2->setName("Bob");

                $user3 = new User();
                $user3->setName("Pigpen");

                $session->store($user1, "users/1-A");
                $session->store($user2, "users/2-A");
                $session->store($user3, "users/3-A");

                $session->countersFor("users/1-A")->increment("downloads", 100);
                $session->countersFor("users/1-A")->increment("likes", 200);

                $session->countersFor("users/2-A")->increment("downloads", 400);
                $session->countersFor("users/2-A")->increment("likes", 800);

                $session->countersFor("users/3-A")->increment("likes", 1600);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->advanced()->rawQuery(CounterResult::class, 'from users select counter("downloads"), counter("likes")')
                        ->toList();

                $this->assertCount(3, $query);

                $this->assertEquals(100, $query[0]->getDownloads());
                $this->assertEquals(200, $query[0]->getLikes());

                $this->assertEquals(400, $query[1]->getDownloads());
                $this->assertEquals(800, $query[1]->getLikes());

                $this->assertNull($query[2]->getDownloads());

                $this->assertEquals(1600, $query[2]->getLikes());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testRawQuerySimpleProjectionWithCounter(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Jerry");

                $user2 = new User();
                $user2->setName("Bob");

                $user3 = new User();
                $user3->setName("Pigpen");

                $session->store($user1, "users/1-A");
                $session->store($user2, "users/2-A");
                $session->store($user3, "users/3-A");

                $session->countersFor("users/1-A")->increment("downloads", 100);
                $session->countersFor("users/2-A")->increment("downloads", 200);
                $session->countersFor("users/3-A")->increment("likes", 400);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->advanced()->rawQuery(CounterResult::class, "from users select name, counter('downloads')")
                        ->toList();

                $this->assertCount(3, $query);

                $this->assertEquals("Jerry", $query[0]->getName());
                $this->assertEquals(100, $query[0]->getDownloads());

                $this->assertEquals("Bob", $query[1]->getName());
                $this->assertEquals(200, $query[1]->getDownloads());

                $this->assertEquals("Pigpen", $query[2]->getName());
                $this->assertNull($query[2]->getDownloads());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testRawQueryJsProjectionWithCounterRawValues(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Jerry");

                $user2 = new User();
                $user2->setName("Bob");

                $user3 = new User();
                $user3->setName("Pigpen");

                $session->store($user1, "users/1-A");
                $session->store($user2, "users/2-A");
                $session->store($user3, "users/3-A");

                $session->countersFor("users/1-A")->increment("downloads", 100);
                $session->countersFor("users/1-A")->increment("likes", 200);

                $session->countersFor("users/2-A")->increment("downloads", 300);
                $session->countersFor("users/2-A")->increment("likes", 400);

                $session->countersFor("users/3-A")->increment("likes", 500);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var array<CounterResult4> $query */
                $query = $session
                        ->advanced()
                        ->rawQuery(CounterResult4::class, "from Users as u select { name: u.name, downloads: counter(u, 'downloads'), likes: counterRaw(u, 'likes') }")
                        ->toList();

                $this->assertCount(3, $query);

                $this->assertEquals("Jerry", $query[0]->getName());
                $this->assertEquals(100, $query[0]->getDownloads());
                $this->assertEquals(200, $query[0]->getLikes()[array_key_first($query[0]->getLikes())]);

                $this->assertEquals("Bob", $query[1]->getName());
                $this->assertEquals(300, $query[1]->getDownloads());
                $this->assertEquals(400, $query[1]->getLikes()[array_key_first($query[1]->getLikes())]);

                $this->assertEquals("Pigpen", $query[2]->getName());
                $this->assertEquals(500, $query[2]->getLikes()[array_key_first($query[2]->getLikes())]);
                $this->assertNull($query[2]->getDownloads());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionQueryIncludeSingleCounter(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order1 = new Order();
                $order1->setCompany("companies/1-A");
                $session->store($order1, "orders/1-A");

                $order2 = new Order();
                $order2->setCompany("companies/2-A");
                $session->store($order2, "orders/2-A");

                $order3 = new Order();
                $order3->setCompany("companies/3-A");
                $session->store($order3, "orders/3-A");

                $session->countersFor("orders/1-A")->increment("downloads", 100);
                $session->countersFor("orders/2-A")->increment("downloads", 200);
                $session->countersFor("orders/3-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->query(Order::class)
                        ->include(function($i) { $i->includeCounter("downloads"); });

                $this->assertEquals("from 'Orders' include counters('downloads')", $query->toString());

                $queryResult = $query->toList();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $order = $queryResult[0];
                $this->assertEquals("orders/1-A", $order->getId());

                $val = $session->countersFor($order)->get("downloads");
                $this->assertEquals(100, $val);

                $order = $queryResult[1];
                $this->assertEquals("orders/2-A", $order->getId());
                $val = $session->countersFor($order)->get("downloads");
                $this->assertEquals(200, $val);

                $order = $queryResult[2];
                $this->assertEquals("orders/3-A", $order->getId());
                $val = $session->countersFor($order)->get("downloads");
                $this->assertEquals(300, $val);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionQueryIncludeMultipleCounters(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order1 = new Order();
                $order1->setCompany("companies/1-A");
                $session->store($order1, "orders/1-A");

                $order2 = new Order();
                $order2->setCompany("companies/2-A");
                $session->store($order2, "orders/2-A");

                $order3 = new Order();
                $order3->setCompany("companies/3-A");
                $session->store($order3, "orders/3-A");

                $session->countersFor("orders/1-A")->increment("downloads", 100);
                $session->countersFor("orders/2-A")->increment("downloads", 200);
                $session->countersFor("orders/3-A")->increment("downloads", 300);

                $session->countersFor("orders/1-A")->increment("likes", 1000);
                $session->countersFor("orders/2-A")->increment("likes", 2000);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->query(Order::class)
                        ->include(function($i) { $i->includeCounters( ["downloads", "likes" ] ); });

                $this->assertEquals("from 'Orders' include counters('downloads'),counters('likes')", $query->toString());

                $queryResult = $query->toList();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // included counters should be in cache
                $order = $queryResult[0];
                $this->assertEquals("orders/1-A", $order->getId());

                $val = $session->countersFor($order)->get("downloads");
                $this->assertEquals(100, $val);
                $val = $session->countersFor($order)->get("likes");
                $this->assertEquals(1000, $val);

                $order = $queryResult[1];
                $this->assertEquals("orders/2-A", $order->getId());
                $val = $session->countersFor($order)->get("downloads");
                $this->assertEquals(200, $val);
                $val = $session->countersFor($order)->get("likes");
                $this->assertEquals(2000, $val);

                $order = $queryResult[2];
                $this->assertEquals("orders/3-A", $order->getId());
                $val = $session->countersFor($order)->get("downloads");
                $this->assertEquals(300, $val);

                //should register missing counters
                $val = $session->countersFor($order)->get("likes");
                $this->assertNull($val);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionQueryIncludeAllCounters(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order1 = new Order();
                $order1->setCompany("companies/1-A");
                $session->store($order1, "orders/1-A");

                $order2 = new Order();
                $order2->setCompany("companies/2-A");
                $session->store($order2, "orders/2-A");

                $order3 = new Order();
                $order3->setCompany("companies/3-A");
                $session->store($order3, "orders/3-A");

                $session->countersFor("orders/1-A")->increment("Downloads", 100);
                $session->countersFor("orders/2-A")->increment("Downloads", 200);
                $session->countersFor("orders/3-A")->increment("Downloads", 300);

                $session->countersFor("orders/1-A")->increment("Likes", 1000);
                $session->countersFor("orders/2-A")->increment("Likes", 2000);

                $session->countersFor("orders/1-A")->increment("Votes", 10000);
                $session->countersFor("orders/3-A")->increment("Cats", 5);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->query(Order::class)
                        ->include(function($i) { $i->includeAllCounters(); });

                $this->assertEquals("from 'Orders' include counters()", $query->toString());

                $queryResult = $query->toList();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // included counters should be in cache
                $order = $queryResult[0];
                $this->assertEquals("orders/1-A", $order->getId());

                $dic = $session->countersFor($order)->getAll();

                $this->assertCount(3, $dic);
                $this->assertArrayHasKey("Downloads", $dic);
                $this->assertEquals(100, $dic['Downloads']);
                $this->assertArrayHasKey("Likes", $dic);
                $this->assertEquals(1000, $dic['Likes']);
                $this->assertArrayHasKey("Votes", $dic);
                $this->assertEquals(10000, $dic['Votes']);

                $order = $queryResult[1];
                $this->assertEquals("orders/2-A", $order->getId());
                $dic = $session->countersFor($order)->getAll();

                $this->assertCount(2, $dic);
                $this->assertArrayHasKey("Downloads", $dic);
                $this->assertEquals(200, $dic['Downloads']);
                $this->assertArrayHasKey("Likes", $dic);
                $this->assertEquals(2000, $dic['Likes']);

                $order = $queryResult[2];
                $this->assertEquals("orders/3-A", $order->getId());
                $dic = $session->countersFor($order)->getAll();

                $this->assertCount(2, $dic);
                $this->assertArrayHasKey("Downloads", $dic);
                $this->assertEquals(300, $dic['Downloads']);
                $this->assertArrayHasKey("Cats", $dic);
                $this->assertEquals(5, $dic['Cats']);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionQueryIncludeCounterAndDocument(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order1 = new Order();
                $order1->setCompany("companies/1-A");
                $session->store($order1, "orders/1-A");

                $order2 = new Order();
                $order2->setCompany("companies/2-A");
                $session->store($order2, "orders/2-A");

                $order3 = new Order();
                $order3->setCompany("companies/3-A");
                $session->store($order3, "orders/3-A");

                $company1 = new Company();
                $company1->setName("HR");
                $session->store($company1, "companies/1-A");

                $company2 = new Company();
                $company2->setName("HP");
                $session->store($company2, "companies/2-A");

                $company3 = new Company();
                $company3->setName("Google");
                $session->store($company3, "companies/3-A");

                $session->countersFor("orders/1-A")->increment("downloads", 100);
                $session->countersFor("orders/2-A")->increment("downloads", 200);
                $session->countersFor("orders/3-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->query(Order::class)
                        ->include(function($i) { $i
                                ->includeCounter("downloads")
                                ->includeDocuments("company");
                        });

                $this->assertEquals("from 'Orders' include company,counters('downloads')", $query->toString());

                $queryResult = $query->toList();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // included documents should be in cache
                $session->load(User::class, "companies/1-A", "companies/2-A", "companies/3-A");
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // included counters should be in cache
                $order = $queryResult[0];
                $this->assertEquals("orders/1-A", $order->getId());
                $val = $session->countersFor($order)->get("downloads");
                $this->assertEquals(100, $val);

                $order = $queryResult[1];
                $this->assertEquals("orders/2-A", $order->getId());
                $val = $session->countersFor($order)->get("downloads");
                $this->assertEquals(200, $val);

                $order = $queryResult[2];
                $this->assertEquals("orders/3-A", $order->getId());
                $val = $session->countersFor($order)->get("downloads");
                $this->assertEquals(300, $val);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionQueryIncludeCounterOfRelatedDocument(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order1 = new Order();
                $order1->setEmployee("employees/1-A");
                $session->store($order1, "orders/1-A");

                $order2 = new Order();
                $order2->setEmployee("employees/2-A");
                $session->store($order2, "orders/2-A");

                $order3 = new Order();
                $order3->setEmployee("employees/3-A");
                $session->store($order3, "orders/3-A");

                $employee1 = new Employee();
                $employee1->setFirstName("Aviv");
                $session->store($employee1, "employees/1-A");

                $employee2 = new Employee();
                $employee2->setFirstName("Jerry");
                $session->store($employee2, "employees/2-A");

                $employee3 = new Employee();
                $employee3->setFirstName("Bob");
                $session->store($employee3, "employees/3-A");

                $session->countersFor("employees/1-A")->increment("downloads", 100);
                $session->countersFor("employees/2-A")->increment("downloads", 200);
                $session->countersFor("employees/3-A")->increment("downloads", 300);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->query(Order::class)
                        ->include(function($i) { $i->includeCounter("employee", "downloads"); });

                $this->assertEquals("from 'Orders' include counters(employee, 'downloads')", $query->toString());

                $results = $query->toList();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // included counters should be in cache
                $val = $session->countersFor("employees/1-A")->get("downloads");
                $this->assertEquals(100, $val);

                $val = $session->countersFor("employees/2-A")->get("downloads");
                $this->assertEquals(200, $val);

                $val = $session->countersFor("employees/3-A")->get("downloads");
                $this->assertEquals(300, $val);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionQueryIncludeCountersOfRelatedDocument(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order1 = new Order();
                $order1->setEmployee("employees/1-A");
                $session->store($order1, "orders/1-A");

                $order2 = new Order();
                $order2->setEmployee("employees/2-A");
                $session->store($order2, "orders/2-A");

                $order3 = new Order();
                $order3->setEmployee("employees/3-A");
                $session->store($order3, "orders/3-A");

                $employee1 = new Employee();
                $employee1->setFirstName("Aviv");
                $session->store($employee1, "employees/1-A");

                $employee2 = new Employee();
                $employee2->setFirstName("Jerry");
                $session->store($employee2, "employees/2-A");

                $employee3 = new Employee();
                $employee3->setFirstName("Bob");
                $session->store($employee3, "employees/3-A");

                $session->countersFor("employees/1-A")->increment("downloads", 100);
                $session->countersFor("employees/2-A")->increment("downloads", 200);
                $session->countersFor("employees/3-A")->increment("downloads", 300);

                $session->countersFor("employees/1-A")->increment("likes", 1000);
                $session->countersFor("employees/2-A")->increment("likes", 2000);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->query(Order::class)
                        ->include(function($i) { $i->includeCounters("employee", [ "downloads", "likes" ]); });

                $this->assertEquals("from 'Orders' include counters(employee, 'downloads'),counters(employee, 'likes')", $query->toString());

                $results = $query->toList();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // included counters should be in cache
                $dic = $session->countersFor("employees/1-A")->get([ "downloads", "likes" ]);
                $this->assertArrayHasKey("downloads", $dic);
                $this->assertEquals(100, $dic['downloads']);
                $this->assertArrayHasKey("likes", $dic);
                $this->assertEquals(1000, $dic['likes']);

                $dic = $session->countersFor("employees/2-A")->get([ "downloads", "likes" ]);
                $this->assertArrayHasKey("downloads", $dic);
                $this->assertEquals(200, $dic['downloads']);
                $this->assertArrayHasKey("likes", $dic);
                $this->assertEquals(2000, $dic['likes']);

                $dic = $session->countersFor("employees/3-A")->get([ "downloads", "likes" ]);
                $this->assertArrayHasKey("downloads", $dic);
                $this->assertEquals(300, $dic['downloads']);

                $this->assertNull($dic["likes"]);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionQueryIncludeCountersOfDocumentAndOfRelatedDocument(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order1 = new Order();
                $order1->setEmployee("employees/1-A");
                $session->store($order1, "orders/1-A");

                $order2 = new Order();
                $order2->setEmployee("employees/2-A");
                $session->store($order2, "orders/2-A");

                $order3 = new Order();
                $order3->setEmployee("employees/3-A");
                $session->store($order3, "orders/3-A");

                $employee1 = new Employee();
                $employee1->setFirstName("Aviv");
                $session->store($employee1, "employees/1-A");

                $employee2 = new Employee();
                $employee2->setFirstName("Jerry");
                $session->store($employee2, "employees/2-A");

                $employee3 = new Employee();
                $employee3->setFirstName("Bob");
                $session->store($employee3, "employees/3-A");

                $session->countersFor("orders/1-A")->increment("likes", 100);
                $session->countersFor("orders/2-A")->increment("likes", 200);
                $session->countersFor("orders/3-A")->increment("likes", 300);

                $session->countersFor("employees/1-A")->increment("downloads", 1000);
                $session->countersFor("employees/2-A")->increment("downloads", 2000);
                $session->countersFor("employees/3-A")->increment("downloads", 3000);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->query(Order::class)
                        ->include(function($i) {
                            $i->includeCounter("likes")->includeCounter("employee", "downloads"); });

                $this->assertEquals("from 'Orders' include counters('likes'),counters(employee, 'downloads')", $query->toString());

                $orders = $query->toList();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // included counters should be in cache
                $order = $orders[0];
                $this->assertEquals("orders/1-A", $order->getId());
                $val = $session->countersFor($order)->get("likes");
                $this->assertEquals(100, $val);

                $order = $orders[1];
                $this->assertEquals("orders/2-A", $order->getId());
                $val = $session->countersFor($order)->get("likes");
                $this->assertEquals(200, $val);

                $order = $orders[2];
                $this->assertEquals("orders/3-A", $order->getId());
                $val = $session->countersFor($order)->get("likes");
                $this->assertEquals(300, $val);

                $val = $session->countersFor("employees/1-A")->get("downloads");
                $this->assertEquals(1000, $val);

                $val = $session->countersFor("employees/2-A")->get("downloads");
                $this->assertEquals(2000, $val);

                $val = $session->countersFor("employees/3-A")->get("downloads");
                $this->assertEquals(3000, $val);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionQueryIncludeAllCountersOfDocumentAndOfRelatedDocument(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order1 = new Order();
                $order1->setEmployee("employees/1-A");
                $session->store($order1, "orders/1-A");

                $order2 = new Order();
                $order2->setEmployee("employees/2-A");
                $session->store($order2, "orders/2-A");

                $order3 = new Order();
                $order3->setEmployee("employees/3-A");
                $session->store($order3, "orders/3-A");

                $employee1 = new Employee();
                $employee1->setFirstName("Aviv");
                $session->store($employee1, "employees/1-A");

                $employee2 = new Employee();
                $employee2->setFirstName("Jerry");
                $session->store($employee2, "employees/2-A");

                $employee3 = new Employee();
                $employee3->setFirstName("Bob");
                $session->store($employee3, "employees/3-A");

                $session->countersFor("orders/1-A")->increment("likes", 100);
                $session->countersFor("orders/2-A")->increment("likes", 200);
                $session->countersFor("orders/3-A")->increment("likes", 300);

                $session->countersFor("orders/1-A")->increment("downloads", 1000);
                $session->countersFor("orders/2-A")->increment("downloads", 2000);

                $session->countersFor("employees/1-A")->increment("likes", 100);
                $session->countersFor("employees/2-A")->increment("likes", 200);
                $session->countersFor("employees/3-A")->increment("likes", 300);
                $session->countersFor("employees/1-A")->increment("downloads", 1000);
                $session->countersFor("employees/2-A")->increment("downloads", 2000);
                $session->countersFor("employees/3-A")->increment("cats", 5);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->query(Order::class)
                        ->include(function($i) { $i
                                ->includeAllCounters()
                                ->includeAllCounters("employee");
                        });

                $this->assertEquals("from 'Orders' include counters(),counters(employee)", $query->toString());

                $orders = $query->toList();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                // included counters should be in cache
                $order = $orders[0];
                $this->assertEquals("orders/1-A", $order->getId());
                $dic = $session->countersFor($order)->getAll();

                $this->assertCount(2, $dic);
                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);
                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(1000, $dic['downloads']);

                $order = $orders[1];
                $this->assertEquals("orders/2-A", $order->getId());

                $dic = $session->countersFor($order)->getAll();
                $this->assertCount(2, $dic);
                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(200, $dic['likes']);
                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(2000, $dic['downloads']);

                $order = $orders[2];
                $this->assertEquals("orders/3-A", $order->getId());

                $dic = $session->countersFor($order)->getAll();
                $this->assertCount(1, $dic);
                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(300, $dic['likes']);


                $dic = $session->countersFor("employees/1-A")->getAll();
                $this->assertCount(2, $dic);
                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(100, $dic['likes']);
                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(1000, $dic['downloads']);

                $dic = $session->countersFor("employees/2-A")->getAll();
                $this->assertCount(2, $dic);
                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(200, $dic['likes']);
                $this->assertArrayHasKey('downloads', $dic);
                $this->assertEquals(2000, $dic['downloads']);

                $dic = $session->countersFor("employees/3-A")->getAll();
                $this->assertCount(2, $dic);
                $this->assertArrayHasKey('likes', $dic);
                $this->assertEquals(300, $dic['likes']);
                $this->assertArrayHasKey('cats', $dic);
                $this->assertEquals(5, $dic['cats']);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCountersShouldBeCachedOnCollection(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");
                $session->countersFor("orders/1-A")->increment("downloads", 100);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->query(Order::class)
                        ->include(function($i) { $i->includeCounter("downloads"); })
                        ->toList();

                $counterValue = $session->countersFor("orders/1-A")->get("downloads");
                $this->assertEquals(100, $counterValue);

                $session->countersFor("orders/1-A")->increment("downloads", 200);
                $session->saveChanges();

                $session->query(Order::class)
                        ->include(function($i) { $i->includeCounters([ "downloads" ]); })
                        ->toList();

                $counterValue = $session->countersFor("orders/1-A")->get("downloads");
                $this->assertEquals(300, $counterValue);

                $session->countersFor("orders/1-A")->increment("downloads", 200);
                $session->saveChanges();

                $session->query(Order::class)
                        ->include(function($i) { $i->includeCounter("downloads"); })
                        ->toList();

                $counterValue = $session->countersFor("orders/1-A")->get("downloads");
                $this->assertEquals(500, $counterValue);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->load(Order::class, "orders/1-A", function($i) { $i->includeCounter("downloads"); });
                $counterValue = $session->countersFor("orders/1-A")->get("downloads");
                $this->assertEquals(500, $counterValue);

                $session->countersFor("orders/1-A")->increment("downloads", 200);
                $session->saveChanges();

                $session->load(Order::class, "order/1-A", function($i) { $i->includeCounter("downloads"); });
                $counterValue = $session->countersFor("orders/1-A")->get("downloads");
                $this->assertEquals(700, $counterValue);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCountersShouldBeCachedOnAllDocsCollection(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");
                $session->countersFor("orders/1-A")->increment("downloads", 100);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()
                        ->rawQuery(null, "from @all_docs include counters(\$p0)")
                        ->addParameter("p0", [ "downloads" ])
                        ->toList();

                $counterValue = $session->countersFor("orders/1-A")->get("downloads");
                $this->assertEquals(100, $counterValue);

                $session->countersFor("orders/1-A")->increment("downloads", 200);
                $session->saveChanges();

                $session->advanced()->rawQuery(null, "from @all_docs include counters()")
                        ->toList();
                $counterValue = $session->countersFor("orders/1-A")->get("downloads");
                $this->assertEquals(300, $counterValue);

                $session->countersFor("orders/1-A")->increment("downloads", 200);
                $session->saveChanges();

                $session
                        ->advanced()
                        ->rawQuery(null, "from @all_docs include counters(\$p0)")
                        ->addParameter("p0", [ "downloads" ])
                        ->toList();

                $counterValue = $session->countersFor("orders/1-A")->get("downloads");
                $this->assertEquals(500, $counterValue);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCountersCachingShouldHandleDeletion_includeCounterDownload(): void
    {
        $this->countersCachingShouldHandleDeletion(function($session) {
            $session->query(Order::class)->include(function($i) { $i->includeCounter("downloads"); })->toList();
        }, null);
    }

    public function testCountersCachingShouldHandleDeletion_includeCounterUpload(): void
    {
        $this->countersCachingShouldHandleDeletion(function($session) {
            $session->query(Order::class)->include(function($i) { $i->includeCounter("uploads"); })->toList();
        }, 300);
    }


    public function testCountersCachingShouldHandleDeletion_includeCounters(): void {
        $this->countersCachingShouldHandleDeletion(function($session) {
            $session->query(Order::class)->include(function($i) { $i->includeCounters([ "downloads", "uploads", "bugs" ]); })->toList();
        }, null);
    }

    public function testCountersCachingShouldHandleDeletion_includeAllCounters(): void
    {
        $this->countersCachingShouldHandleDeletion(function($session) {
            $session->query(Order::class)->include(function($i) {$i->includeAllCounters(); })->toList();
        }, null);
    }

    private function countersCachingShouldHandleDeletion(Closure $sessionConsumer, ?int $expectedCounterValue): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");

                $session->countersFor("orders/1-A")->increment("downloads", 100);
                $session->countersFor("orders/1-A")->increment("uploads", 123);
                $session->countersFor("orders/1-A")->increment("bugs", 0xDEAD);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->query(Order::class)
                        ->include(function($i) { $i->includeCounter("downloads"); })
                        ->toList();

                $counterValue = $session->countersFor("orders/1-A")->get("downloads");
                $this->assertEquals(100, $counterValue);

                $writeSession = $store->openSession();
                try {
                    $writeSession->countersFor("orders/1-A")->increment("downloads", 200);
                    $writeSession->saveChanges();
                } finally {
                    $writeSession->close();
                }

                $session->query(Order::class)->include(function($i) { $i->includeCounter("downloads"); })->toList();

                $counterValue = $session->countersFor("orders/1-A")->get("downloads");
                $this->assertEquals(300, $counterValue);
                $session->saveChanges();

                $writeSession = $store->openSession();
                try {
                    $writeSession->countersFor("orders/1-A")->delete("downloads");
                    $writeSession->saveChanges();
                } finally {
                    $writeSession->close();
                }

                $sessionConsumer($session);

                $counterValue = $session->countersFor("orders/1-A")->get("downloads");

                $this->assertEquals($expectedCounterValue, $counterValue);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

}
