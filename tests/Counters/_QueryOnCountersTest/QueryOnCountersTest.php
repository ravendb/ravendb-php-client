<?php

namespace tests\RavenDB\Counters\_QueryOnCountersTest;

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

//    public function sessionQueryIncludeSingleCounter(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $session = $store->openSession();
//            try {
//                $order1 = new Order();
//                order1.setCompany("companies/1-A");
//                $session->store(order1, "orders/1-A");
//
//                $order2 = new Order();
//                order2.setCompany("companies/2-A");
//                $session->store(order2, "orders/2-A");
//
//                $order3 = new Order();
//                order3.setCompany("companies/3-A");
//                $session->store(order3, "orders/3-A");
//
//                $session->countersFor("orders/1-A")->increment("downloads", 100);
//                $session->countersFor("orders/2-A")->increment("downloads", 200);
//                $session->countersFor("orders/3-A")->increment("downloads", 300);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            $session = $store->openSession();
//            try {
//                IDocumentQuery<Order> query = $session->query(Order::class)
//                        .include(i -> i.includeCounter("downloads"));
//
//                assertThat(query.toString())
//                        .isEqualTo("from 'Orders' include counters('downloads')");
//
//                List<Order> queryResult = query->toList();
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//
//                $order = queryResult.get(0);
//                assertThat(order.getId())
//                        .isEqualTo("orders/1-A");
//
//                Long val = $session->countersFor(order).get("downloads");
//                assertThat(val)
//                        .isEqualTo(100);
//
//                order = queryResult.get(1);
//                assertThat(order.getId())
//                        .isEqualTo("orders/2-A");
//                val = $session->countersFor(order).get("downloads");
//                assertThat(val)
//                        .isEqualTo(200);
//
//                order = queryResult.get(2);
//                assertThat(order.getId())
//                        .isEqualTo("orders/3-A");
//                val = $session->countersFor(order).get("downloads");
//                assertThat(val)
//                        .isEqualTo(300);
//
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function sessionQueryIncludeMultipleCounters(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $session = $store->openSession();
//            try {
//                $order1 = new Order();
//                order1.setCompany("companies/1-A");
//                $session->store(order1, "orders/1-A");
//
//                $order2 = new Order();
//                order2.setCompany("companies/2-A");
//                $session->store(order2, "orders/2-A");
//
//                $order3 = new Order();
//                order3.setCompany("companies/3-A");
//                $session->store(order3, "orders/3-A");
//
//                $session->countersFor("orders/1-A")->increment("downloads", 100);
//                $session->countersFor("orders/2-A")->increment("downloads", 200);
//                $session->countersFor("orders/3-A")->increment("downloads", 300);
//
//                $session->countersFor("orders/1-A")->increment("likes", 1000);
//                $session->countersFor("orders/2-A")->increment("likes", 2000);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            $session = $store->openSession();
//            try {
//                IDocumentQuery<Order> query = $session->query(Order::class)
//                        .include(i -> i.includeCounters(new String[]{"downloads", "likes"}));
//
//                assertThat(query.toString())
//                        .isEqualTo("from 'Orders' include counters('downloads'),counters('likes')");
//
//                List<Order> queryResult = query->toList();
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//
//                // included counters should be in cache
//                $order = queryResult.get(0);
//                assertThat(order.getId())
//                        .isEqualTo("orders/1-A");
//
//                Long val = $session->countersFor(order).get("downloads");
//                assertThat(val)
//                        .isEqualTo(100);
//                val = $session->countersFor(order).get("likes");
//                assertThat(val)
//                        .isEqualTo(1000);
//
//                order = queryResult.get(1);
//                assertThat(order.getId())
//                        .isEqualTo("orders/2-A");
//                val = $session->countersFor(order).get("downloads");
//                assertThat(val)
//                        .isEqualTo(200);
//                val = $session->countersFor(order).get("likes");
//                assertThat(val)
//                        .isEqualTo(2000);
//
//                order = queryResult.get(2);
//                assertThat(order.getId())
//                        .isEqualTo("orders/3-A");
//                val = $session->countersFor(order).get("downloads");
//                assertThat(val)
//                        .isEqualTo(300);
//
//                //should register missing counters
//                val = $session->countersFor(order).get("likes");
//                assertThat(val)
//                        .isNull();
//
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function sessionQueryIncludeAllCounters(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $session = $store->openSession();
//            try {
//                $order1 = new Order();
//                order1.setCompany("companies/1-A");
//                $session->store(order1, "orders/1-A");
//
//                $order2 = new Order();
//                order2.setCompany("companies/2-A");
//                $session->store(order2, "orders/2-A");
//
//                $order3 = new Order();
//                order3.setCompany("companies/3-A");
//                $session->store(order3, "orders/3-A");
//
//                $session->countersFor("orders/1-A")->increment("Downloads", 100);
//                $session->countersFor("orders/2-A")->increment("Downloads", 200);
//                $session->countersFor("orders/3-A")->increment("Downloads", 300);
//
//                $session->countersFor("orders/1-A")->increment("Likes", 1000);
//                $session->countersFor("orders/2-A")->increment("Likes", 2000);
//
//                $session->countersFor("orders/1-A")->increment("Votes", 10000);
//                $session->countersFor("orders/3-A")->increment("Cats", 5);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            $session = $store->openSession();
//            try {
//                IDocumentQuery<Order> query = $session->query(Order::class)
//                        .include(i -> i.includeAllCounters());
//
//                assertThat(query.toString())
//                        .isEqualTo("from 'Orders' include counters()");
//
//                List<Order> queryResult = query->toList();
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//
//                // included counters should be in cache
//                $order = queryResult.get(0);
//                assertThat(order.getId())
//                        .isEqualTo("orders/1-A");
//
//                Map<String, Long> dic = $session->countersFor(order).getAll();
//                assertThat(dic)
//                        .hasSize(3)
//                        .containsEntry("Downloads", 100L)
//                        .containsEntry("Likes", 1000L)
//                        .containsEntry("Votes", 10000L);
//
//                order = queryResult.get(1);
//                assertThat(order.getId())
//                        .isEqualTo("orders/2-A");
//                dic = $session->countersFor(order).getAll();
//
//                assertThat(dic)
//                        .hasSize(2)
//                        .containsEntry("Downloads", 200L)
//                        .containsEntry("Likes", 2000L);
//
//                order = queryResult.get(2);
//                assertThat(order.getId())
//                        .isEqualTo("orders/3-A");
//                dic = $session->countersFor(order).getAll();
//                assertThat(dic)
//                        .hasSize(2)
//                        .containsEntry("Downloads", 300L)
//                        .containsEntry("Cats", 5L);
//
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function sessionQueryIncludeCounterAndDocument(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $session = $store->openSession();
//            try {
//                $order1 = new Order();
//                order1.setCompany("companies/1-A");
//                $session->store(order1, "orders/1-A");
//
//                $order2 = new Order();
//                order2.setCompany("companies/2-A");
//                $session->store(order2, "orders/2-A");
//
//                $order3 = new Order();
//                order3.setCompany("companies/3-A");
//                $session->store(order3, "orders/3-A");
//
//                Company company1 = new Company();
//                company1->setName("HR");
//                $session->store(company1, "companies/1-A");
//
//                Company company2 = new Company();
//                company2->setName("HP");
//                $session->store(company2, "companies/2-A");
//
//                Company company3 = new Company();
//                company3->setName("Google");
//                $session->store(company3, "companies/3-A");
//
//                $session->countersFor("orders/1-A")->increment("downloads", 100);
//                $session->countersFor("orders/2-A")->increment("downloads", 200);
//                $session->countersFor("orders/3-A")->increment("downloads", 300);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            $session = $store->openSession();
//            try {
//                IDocumentQuery<Order> query = $session->query(Order::class)
//                        .include(i -> i
//                                .includeCounter("downloads")
//                                .includeDocuments("company"));
//
//                assertThat(query.toString())
//                        .isEqualTo("from 'Orders' include company,counters('downloads')");
//
//                List<Order> queryResult = query->toList();
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//
//                // included documents should be in cache
//                $session->load(User::class, "companies/1-A", "companies/2-A", "companies/3-A");
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//
//                // included counters should be in cache
//                $order = queryResult.get(0);
//                assertThat(order.getId())
//                        .isEqualTo("orders/1-A");
//                Long val = $session->countersFor(order).get("downloads");
//                assertThat(val)
//                        .isEqualTo(100);
//
//                order = queryResult.get(1);
//                assertThat(order.getId())
//                        .isEqualTo("orders/2-A");
//                val = $session->countersFor(order).get("downloads");
//                assertThat(val)
//                        .isEqualTo(200);
//
//                order = queryResult.get(2);
//                assertThat(order.getId())
//                        .isEqualTo("orders/3-A");
//                val = $session->countersFor(order).get("downloads");
//                assertThat(val)
//                        .isEqualTo(300);
//
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function sessionQueryIncludeCounterOfRelatedDocument(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $session = $store->openSession();
//            try {
//                $order1 = new Order();
//                order1.setEmployee("employees/1-A");
//                $session->store(order1, "orders/1-A");
//
//                $order2 = new Order();
//                order2.setEmployee("employees/2-A");
//                $session->store(order2, "orders/2-A");
//
//                $order3 = new Order();
//                order3.setEmployee("employees/3-A");
//                $session->store(order3, "orders/3-A");
//
//                Employee employee1 = new Employee();
//                employee1.setFirstName("Aviv");
//                $session->store(employee1, "employees/1-A");
//
//                Employee employee2 = new Employee();
//                employee2.setFirstName("Jerry");
//                $session->store(employee2, "employees/2-A");
//
//                Employee employee3 = new Employee();
//                employee3.setFirstName("Bob");
//                $session->store(employee3, "employees/3-A");
//
//                $session->countersFor("employees/1-A")->increment("downloads", 100);
//                $session->countersFor("employees/2-A")->increment("downloads", 200);
//                $session->countersFor("employees/3-A")->increment("downloads", 300);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            $session = $store->openSession();
//            try {
//                IDocumentQuery<Order> query = $session->query(Order::class)
//                        .include(i -> i.includeCounter("employee", "downloads"));
//
//                assertThat(query.toString())
//                        .isEqualTo("from 'Orders' include counters(employee, 'downloads')");
//
//                List<Order> results = query->toList();
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//
//                // included counters should be in cache
//                Long val = $session->countersFor("employees/1-A").get("downloads");
//                assertThat(val)
//                        .isEqualTo(100);
//
//                val = $session->countersFor("employees/2-A").get("downloads");
//                assertThat(val)
//                        .isEqualTo(200);
//
//                val = $session->countersFor("employees/3-A").get("downloads");
//                assertThat(val)
//                        .isEqualTo(300);
//
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function sessionQueryIncludeCountersOfRelatedDocument(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $session = $store->openSession();
//            try {
//                $order1 = new Order();
//                order1.setEmployee("employees/1-A");
//                $session->store(order1, "orders/1-A");
//
//                $order2 = new Order();
//                order2.setEmployee("employees/2-A");
//                $session->store(order2, "orders/2-A");
//
//                $order3 = new Order();
//                order3.setEmployee("employees/3-A");
//                $session->store(order3, "orders/3-A");
//
//                Employee employee1 = new Employee();
//                employee1.setFirstName("Aviv");
//                $session->store(employee1, "employees/1-A");
//
//                Employee employee2 = new Employee();
//                employee2.setFirstName("Jerry");
//                $session->store(employee2, "employees/2-A");
//
//                Employee employee3 = new Employee();
//                employee3.setFirstName("Bob");
//                $session->store(employee3, "employees/3-A");
//
//                $session->countersFor("employees/1-A")->increment("downloads", 100);
//                $session->countersFor("employees/2-A")->increment("downloads", 200);
//                $session->countersFor("employees/3-A")->increment("downloads", 300);
//
//                $session->countersFor("employees/1-A")->increment("likes", 1000);
//                $session->countersFor("employees/2-A")->increment("likes", 2000);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            $session = $store->openSession();
//            try {
//                IDocumentQuery<Order> query = $session->query(Order::class)
//                        .include(i -> i.includeCounters("employee", new String[]{"downloads", "likes"}));
//
//                assertThat(query.toString())
//                        .isEqualTo("from 'Orders' include counters(employee, 'downloads'),counters(employee, 'likes')");
//
//                List<Order> results = query->toList();
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//
//                // included counters should be in cache
//                Map<String, Long> dic = $session->countersFor("employees/1-A").get(Arrays.asList("downloads", "likes"));
//                assertThat(dic)
//                        .containsEntry("downloads", 100L)
//                        .containsEntry("likes", 1000L);
//
//                dic = $session->countersFor("employees/2-A").get(Arrays.asList("downloads", "likes"));
//                assertThat(dic)
//                        .containsEntry("downloads", 200L)
//                        .containsEntry("likes", 2000L);
//
//                dic = $session->countersFor("employees/3-A").get(Arrays.asList("downloads", "likes"));
//                assertThat(dic)
//                        .containsEntry("downloads", 300L);
//
//                assertThat(dic.get("likes"))
//                        .isNull();
//
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function sessionQueryIncludeCountersOfDocumentAndOfRelatedDocument(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $session = $store->openSession();
//            try {
//                $order1 = new Order();
//                order1.setEmployee("employees/1-A");
//                $session->store(order1, "orders/1-A");
//
//                $order2 = new Order();
//                order2.setEmployee("employees/2-A");
//                $session->store(order2, "orders/2-A");
//
//                $order3 = new Order();
//                order3.setEmployee("employees/3-A");
//                $session->store(order3, "orders/3-A");
//
//                Employee employee1 = new Employee();
//                employee1.setFirstName("Aviv");
//                $session->store(employee1, "employees/1-A");
//
//                Employee employee2 = new Employee();
//                employee2.setFirstName("Jerry");
//                $session->store(employee2, "employees/2-A");
//
//                Employee employee3 = new Employee();
//                employee3.setFirstName("Bob");
//                $session->store(employee3, "employees/3-A");
//
//                $session->countersFor("orders/1-A")->increment("likes", 100);
//                $session->countersFor("orders/2-A")->increment("likes", 200);
//                $session->countersFor("orders/3-A")->increment("likes", 300);
//
//                $session->countersFor("employees/1-A")->increment("downloads", 1000);
//                $session->countersFor("employees/2-A")->increment("downloads", 2000);
//                $session->countersFor("employees/3-A")->increment("downloads", 3000);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            $session = $store->openSession();
//            try {
//                IDocumentQuery<Order> query = $session->query(Order::class)
//                        .include(i -> i.includeCounter("likes").includeCounter("employee", "downloads"));
//
//                assertThat(query.toString())
//                        .isEqualTo("from 'Orders' include counters('likes'),counters(employee, 'downloads')");
//
//                List<Order> orders = query->toList();
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//
//                // included counters should be in cache
//                $order = orders.get(0);
//                assertThat(order.getId())
//                        .isEqualTo("orders/1-A");
//                Long val = $session->countersFor(order).get("likes");
//                assertThat(val)
//                        .isEqualTo(100);
//
//                order = orders.get(1);
//                assertThat(order.getId())
//                        .isEqualTo("orders/2-A");
//                val = $session->countersFor(order).get("likes");
//                assertThat(val)
//                        .isEqualTo(200);
//
//                order = orders.get(2);
//                assertThat(order.getId())
//                        .isEqualTo("orders/3-A");
//                val = $session->countersFor(order).get("likes");
//                assertThat(val)
//                        .isEqualTo(300);
//
//                val = $session->countersFor("employees/1-A").get("downloads");
//                assertThat(val)
//                        .isEqualTo(1000);
//
//                val = $session->countersFor("employees/2-A").get("downloads");
//                assertThat(val)
//                        .isEqualTo(2000);
//
//                val = $session->countersFor("employees/3-A").get("downloads");
//                assertThat(val)
//                        .isEqualTo(3000);
//
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function sessionQueryIncludeAllCountersOfDocumentAndOfRelatedDocument(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $session = $store->openSession();
//            try {
//                $order1 = new Order();
//                order1.setEmployee("employees/1-A");
//                $session->store(order1, "orders/1-A");
//
//                $order2 = new Order();
//                order2.setEmployee("employees/2-A");
//                $session->store(order2, "orders/2-A");
//
//                $order3 = new Order();
//                order3.setEmployee("employees/3-A");
//                $session->store(order3, "orders/3-A");
//
//                Employee employee1 = new Employee();
//                employee1.setFirstName("Aviv");
//                $session->store(employee1, "employees/1-A");
//
//                Employee employee2 = new Employee();
//                employee2.setFirstName("Jerry");
//                $session->store(employee2, "employees/2-A");
//
//                Employee employee3 = new Employee();
//                employee3.setFirstName("Bob");
//                $session->store(employee3, "employees/3-A");
//
//                $session->countersFor("orders/1-A")->increment("likes", 100);
//                $session->countersFor("orders/2-A")->increment("likes", 200);
//                $session->countersFor("orders/3-A")->increment("likes", 300);
//
//                $session->countersFor("orders/1-A")->increment("downloads", 1000);
//                $session->countersFor("orders/2-A")->increment("downloads", 2000);
//
//                $session->countersFor("employees/1-A")->increment("likes", 100);
//                $session->countersFor("employees/2-A")->increment("likes", 200);
//                $session->countersFor("employees/3-A")->increment("likes", 300);
//                $session->countersFor("employees/1-A")->increment("downloads", 1000);
//                $session->countersFor("employees/2-A")->increment("downloads", 2000);
//                $session->countersFor("employees/3-A")->increment("cats", 5);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            $session = $store->openSession();
//            try {
//                IDocumentQuery<Order> query = $session->query(Order::class)
//                        .include(i -> i
//                                .includeAllCounters()
//                                .includeAllCounters("employee"));
//
//                assertThat(query.toString())
//                        .isEqualTo("from 'Orders' include counters(),counters(employee)");
//
//                List<Order> orders = query->toList();
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//
//                // included counters should be in cache
//                $order = orders.get(0);
//                assertThat(order.getId())
//                        .isEqualTo("orders/1-A");
//                Map<String, Long> dic = $session->countersFor(order).getAll();
//                assertThat(dic)
//                        .hasSize(2)
//                        .containsEntry("likes", 100L)
//                        .containsEntry("downloads", 1000L);
//
//                order = orders.get(1);
//                assertThat(order.getId())
//                        .isEqualTo("orders/2-A");
//
//                dic = $session->countersFor(order).getAll();
//                assertThat(dic)
//                        .hasSize(2)
//                        .containsEntry("likes", 200L)
//                        .containsEntry("downloads", 2000L);
//
//                order = orders.get(2);
//                assertThat(order.getId())
//                        .isEqualTo("orders/3-A");
//
//                dic = $session->countersFor(order).getAll();
//                assertThat(dic)
//                        .hasSize(1)
//                        .containsEntry("likes", 300L);
//
//                dic = $session->countersFor("employees/1-A").getAll();
//                assertThat(dic)
//                        .hasSize(2)
//                        .containsEntry("likes", 100L)
//                        .containsEntry("downloads", 1000L);
//
//                dic = $session->countersFor("employees/2-A").getAll();
//                assertThat(dic)
//                        .hasSize(2)
//                        .containsEntry("likes", 200L)
//                        .containsEntry("downloads", 2000L);
//
//                dic = $session->countersFor("employees/3-A").getAll();
//                assertThat(dic)
//                        .hasSize(2)
//                        .containsEntry("likes", 300L)
//                        .containsEntry("cats", 5L);
//
//                assertThat(session->advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function countersShouldBeCachedOnCollection(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $session = $store->openSession();
//            try {
//                $order = new Order();
//                $order->setCompany("companies/1-A");
//                $session->store($order, "orders/1-A");
//                $session->countersFor("orders/1-A")->increment("downloads", 100);
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            $session = $store->openSession();
//            try {
//                $session->query(Order::class)
//                        .include(i -> i.includeCounter("downloads"))
//                        ->toList();
//
//                Long counterValue = $session->countersFor("orders/1-A")
//                        .get("downloads");
//                assertThat(counterValue)
//                        .isEqualTo(100);
//
//                $session->countersFor("orders/1-A")->increment("downloads", 200);
//                $session->saveChanges();
//
//                $session->query(Order::class)
//                        .include(i -> i.includeCounters(new String[] { "downloads" }))
//                        ->toList();
//
//                counterValue = $session->countersFor("orders/1-A").get("downloads");
//                assertThat(counterValue)
//                        .isEqualTo(300);
//
//                $session->countersFor("orders/1-A")->increment("downloads", 200);
//                $session->saveChanges();
//
//                $session->query(Order::class)
//                        .include(i -> i.includeCounter("downloads"))
//                        ->toList();
//
//                counterValue = $session->countersFor("orders/1-A").get("downloads");
//                assertThat(counterValue)
//                        .isEqualTo(500);
//            } finally {
//                $session->close();
//            }
//
//            $session = $store->openSession();
//            try {
//                $session->load(Order::class, "orders/1-A", i -> i.includeCounter("downloads"));
//                Long counterValue = $session->countersFor("orders/1-A").get("downloads");
//                assertThat(counterValue)
//                        .isEqualTo(500);
//
//                $session->countersFor("orders/1-A")->increment("downloads", 200);
//                $session->saveChanges();
//
//                $session->load(Order::class, "order/1-A", i -> i.includeCounter("downloads"));
//                counterValue = $session->countersFor("orders/1-A").get("downloads");
//                assertThat(counterValue)
//                        .isEqualTo(700);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }

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

//    public function countersCachingShouldHandleDeletion_includeCounterDownload(): void {
//        countersCachingShouldHandleDeletion(session -> {
//            $session->query(Order::class).include(i -> i.includeCounter("downloads"))->toList();
//        }, null);
//    }
//
//    @Test
//    public function countersCachingShouldHandleDeletion_includeCounterUpload(): void {
//        countersCachingShouldHandleDeletion(session -> {
//            $session->query(Order::class).include(i -> i.includeCounter("uploads"))->toList();
//        }, 300L);
//    }
//
//    @Test
//    public function countersCachingShouldHandleDeletion_includeCounters(): void {
//        countersCachingShouldHandleDeletion(session -> {
//            $session->query(Order::class).include(i -> i.includeCounters(new String[] { "downloads", "uploads", "bugs" }))->toList();
//        }, null);
//    }
//
//    @Test
//    public function countersCachingShouldHandleDeletion_includeAllCounters(): void {
//        countersCachingShouldHandleDeletion(session -> {
//            $session->query(Order::class).include(i -> i.includeAllCounters())->toList();
//        }, null);
//    }
//
//    private void countersCachingShouldHandleDeletion(Consumer<IDocumentSession> sessionConsumer, Long expectedCounterValue): void {
//        $store = $this->getDocumentStore();
//        try {
//            $session = $store->openSession();
//            try {
//                $order = new Order();
//                $order->setCompany("companies/1-A");
//                $session->store($order, "orders/1-A");
//
//                $session->countersFor("orders/1-A")->increment("downloads", 100);
//                $session->countersFor("orders/1-A")->increment("uploads", 123);
//                $session->countersFor("orders/1-A")->increment("bugs", 0xDEAD);
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            $session = $store->openSession();
//            try {
//                $session->query(Order::class)
//                        .include(i -> i.includeCounter("downloads"))
//                        ->toList();
//
//                Long counterValue = $session->countersFor("orders/1-A").get("downloads");
//                assertThat(counterValue)
//                        .isEqualTo(100);
//
//                try (IDocumentSession writeSession = store.openSession()) {
//                    writeSession.countersFor("orders/1-A")->increment("downloads", 200);
//                    writeSession.saveChanges();
//                }
//
//                $session->query(Order::class).include(i -> i.includeCounter("downloads"))->toList();
//
//                counterValue = $session->countersFor("orders/1-A").get("downloads");
//                assertThat(counterValue)
//                        .isEqualTo(300);
//                $session->saveChanges();
//
//                try (IDocumentSession writeSession = store.openSession()) {
//                    writeSession.countersFor("orders/1-A").delete("downloads");
//                    writeSession.saveChanges();
//                }
//
//                sessionConsumer.accept(session);
//
//                counterValue = $session->countersFor("orders/1-A").get("downloads");
//
//                assertThat(counterValue)
//                        .isEqualTo(expectedCounterValue);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
}
