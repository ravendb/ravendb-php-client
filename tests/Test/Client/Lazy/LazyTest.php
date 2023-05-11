<?php

namespace tests\RavenDB\Test\Client\Lazy;

use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class LazyTest extends RemoteTestBase
{
    public function testCanLazilyLoadEntity(): void {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                for ($i = 1; $i <= 6; $i++) {
                    $company = new Company();
                    $company->setId("companies/" . $i);
                    $session->store($company, "companies/" . $i);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $lazyOrder = $session->advanced()->lazily()->load(Company::class, "companies/1");
                $this->assertFalse($lazyOrder->isValueCreated());

                $order = $lazyOrder->getValue();
                $this->assertEquals("companies/1", $order->getId());

                $lazyOrders = $session->advanced()->lazily()->load(Company::class, ["companies/1", "companies/2"]);
                $this->assertFalse($lazyOrders->isValueCreated());

                $orders = $lazyOrders->getValue();
                $this->assertCount(2, $orders);

                $company1 = $orders["companies/1"];
                $company2 = $orders["companies/2"];

                $this->assertNotNull($company1);
                $this->assertNotNull($company2);

                $this->assertEquals("companies/1", $company1->getId());

                $this->assertEquals("companies/2", $company2->getId());

                $lazyOrder = $session->advanced()->lazily()->load(Company::class, "companies/3");

                $this->assertFalse($lazyOrder->isValueCreated());

                $order = $lazyOrder->getValue();

                $this->assertEquals("companies/3", $order->getId());

                $load = $session->advanced()->lazily()->load(Company::class, [ "no_such_1", "no_such_2" ]);
                $missingItems = $load->getValue();

                $this->assertNull($missingItems["no_such_1"]);
                $this->assertNull($missingItems["no_such_2"]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanExecuteAllPendingLazyOperations(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company1 = new Company();
                $company1->setId("companies/1");
                $session->store($company1, "companies/1");

                $company2 = new Company();
                $company2->setId("companies/2");
                $session->store($company2, "companies/2");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company1Ref = null;
                $company2Ref = null;
                $session->advanced()->lazily()->load(Company::class, "companies/1", function($x) use (&$company1Ref) { $company1Ref = $x; });
                $session->advanced()->lazily()->load(Company::class, "companies/2", function($x) use (&$company2Ref) { $company2Ref = $x; });

                $this->assertNull($company1Ref);
                $this->assertNull($company2Ref);

                $session->advanced()->eagerly()->executeAllPendingLazyOperations();

                $this->assertNotNull($company1Ref);
                $this->assertNotNull($company2Ref);

                $this->assertEquals("companies/1", $company1Ref->getId());
                $this->assertEquals("companies/2", $company2Ref->getId());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testWithQueuedActions_Load(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setLastName("Oren");
                $session->store($user, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $userRef = null;

                $session->advanced()->lazily()->load(User::class, "users/1", function($x) use (&$userRef) { $userRef = $x; });

                $session->advanced()->eagerly()->executeAllPendingLazyOperations();

                $this->assertNotNull($userRef);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanUseCacheWhenLazyLoading(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setLastName("Oren");
                $session->store($user, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->lazily()->load(User::class, "users/1")->getValue();

                $oldRequestCount = $session->advanced()->getNumberOfRequests();

                $user = $session->advanced()->lazily()->load(User::class, "users/1")->getValue();

                $this->assertEquals("Oren", $user->getLastName());

                $this->assertEquals($oldRequestCount, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testDontLazyLoadAlreadyLoadedValues(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setLastName("Oren");
                $session->store($user, "users/1");

                $user2 = new User();
                $user2->setLastName("Marcin");
                $session->store($user2, "users/2");

                $user3 = new User();
                $user3->setLastName("John");
                $session->store($user3, "users/3");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {

                $lazyLoad = $session->advanced()->lazily()->load(User::class, [ "users/2", "users/3" ]);
                $session->advanced()->lazily()->load(User::class, [ "users/1", "users/3" ]);

                $session->load(User::class, "users/2");
                $session->load(User::class, "users/3");

                $session->advanced()->eagerly()->executeAllPendingLazyOperations();

                $this->assertTrue($session->advanced()->isLoaded("users/1"));

                $users = $lazyLoad->getValue();
                $this->assertCount(2, $users);

                $oldRequestCount = $session->advanced()->getNumberOfRequests();
                $lazyLoad = $session->advanced()->lazily()->load(User::class, [ "users/3" ]);
                $session->advanced()->eagerly()->executeAllPendingLazyOperations();

                $this->assertEquals($oldRequestCount, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
