<?php

namespace tests\RavenDB\Bugs\Caching\_CachingOfDocumentIncludeTest;

use tests\RavenDB\Infrastructure\Entity\Order;
use tests\RavenDB\Infrastructure\Entity\OrderLine;
use tests\RavenDB\Infrastructure\Entity\OrderLineList;
use tests\RavenDB\RemoteTestBase;

class CachingOfDocumentIncludeTest extends RemoteTestBase
{
    public function test_can_cache_document_with_includes(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Ayende");
                $session->store($user);

                $partner = new User();
                $partner->setPartnerId("users/1-A");
                $session->store($partner);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->include("partnerId")
                    ->load(User::class, "users/2-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->include("partnerId")
                    ->load(User::class, "users/2-A");

                $this->assertEquals(1, $session->advanced()->getRequestExecutor()->getCache()->getNumberOfItems());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_can_avoid_using_server_for_load_with_include_if_everything_is_in_session_cacheAsync(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Ayende");
                $session->store($user);

                $partner = new User();
                $partner->setPartnerId("users/1-A");
                $session->store($partner);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/2-A");

                $session->load(User::class, $user->getPartnerId());

                $old = $session->advanced()->getNumberOfRequests();
                $newUser = $session->include("partnerId")
                    ->load(User::class, "users/2-A");

                $this->assertEquals($old, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_can_avoid_using_server_for_load_with_include_if_everything_is_in_session_cacheLazy(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Ayende");
                $session->store($user);

                $partner = new User();
                $partner->setPartnerId("users/1-A");
                $session->store($partner);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->lazily()->load(User::class, "users/2-A");
                $session->advanced()->lazily()->load(User::class, "users/1-A");
                $session->advanced()->eagerly()->executeAllPendingLazyOperations();

                $old = $session->advanced()->getNumberOfRequests();

                $result1 = $session->advanced()->lazily()
                        ->include("partnerId")
                        ->load(User::class, "users/2-A");

                $this->assertNotNull($result1->getValue());
                $this->assertEquals($old, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_can_avoid_using_server_for_load_with_include_if_everything_is_in_session_cache(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Ayende");
                $session->store($user);

                $partner = new User();
                $partner->setPartnerId("users/1-A");
                $session->store($partner);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/2-A");

                $session->load(User::class, $user->getPartnerId());

                $old = $session->advanced()->getNumberOfRequests();

                $res = $session->include("partnerId")
                    ->load(User::class, "users/2-A");

                $this->assertEquals($old, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_can_avoid_using_server_for_multiload_with_include_if_everything_is_in_session_cache(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $storeUser = function ($name) use ($session) {
                    $user = new User();
                    $user->setName($name);
                    $session->store($user);
                };

                $storeUser("Additional");
                $storeUser("Ayende");
                $storeUser("Michael");
                $storeUser("Fitzchak");
                $storeUser("Maxim");

                $withPartner = new User();
                $withPartner->setPartnerId("users/1-A");
                $session->store($withPartner);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $u2 = $session->load(User::class, "users/2-A");
                $u6 = $session->load(User::class, "users/6-A");

                $inp = [];
                $inp[] = "users/1-A";
                $inp[] = "users/2-A";
                $inp[] = "users/3-A";
                $inp[] = "users/4-A";
                $inp[] = "users/5-A";
                $inp[] = "users/6-A";
                $u4 = $session->load(User::class, $inp);

                $session->load(User::class, $u6->getPartnerId());

                $old = $session->advanced()->getNumberOfRequests();

                $res = $session->include("partnerId")
                    ->load(User::class, "users/2-A", "users/3-A", "users/6-A");

                $this->assertEquals($old, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_can_include_nested_paths(): void
    {
        $store = $this->getDocumentStore();
        try {
            $order = new Order();

            $orderLine1 = new OrderLine();
            $orderLine1->setProduct("products/1-A");
            $orderLine1->setProductName("phone");

            $orderLine2 = new OrderLine();
            $orderLine2->setProduct("products/2-A");
            $orderLine2->setProductName("mouse");

            $order->setLines(OrderLineList::fromArray([$orderLine1, $orderLine2]));

            $product1 = new Product();
            $product1->setId("products/1-A");
            $product1->setName("phone");

            $product2 = new Product();
            $product2->setId("products/2-A");
            $product2->setName("mouse");

            $session = $store->openSession();
            try {
                $session->store($order, "orders/1-A");
                $session->store($product1);
                $session->store($product2);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $this->assertEquals(0, $session->advanced()->getNumberOfRequests());
                $orders = $session->query(Order::class)
                    ->include(function ($x) {
                        return $x->includeDocuments("lines[].product");
                    })
                    ->toList();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $product = $session->load(Product::class, $orders[0]->getLines()[0]->getProduct());
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

            } finally {
                $session->close();
            }


            $session = $store->openSession();
            try {
                $this->assertEquals(0, $session->advanced()->getNumberOfRequests());
                $orders = $session->query(Order::class)
                    ->include(function ($x) {
                        return $x->includeDocuments("lines.product");
                    })
                    ->toList();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $product = $session->load(Product::class, $orders[0]->getLines()[0]->getProduct());
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
