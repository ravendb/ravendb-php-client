<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class JavaScriptIndexTest extends RemoteTestBase
{
    public function testCanUseJavaScriptIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new UsersByName());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Brendan Eich");

                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $single = $session->query(User::class, "UsersByName")
                    ->whereEquals("name", "Brendan Eich")
                    ->single();

                $this->assertNotNull($single);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    /** @doesNotPerformAssertions */
    public function testCanUseJavaScriptIndexWithAdditionalSources(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new UsersByNameWithAdditionalSources());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Brendan Eich");
                $session->store($user);
                $session->saveChanges();

                $this->waitForIndexing($store);

                $session->query(User::class, "UsersByNameWithAdditionalSources")
                        ->whereEquals("name", "Mr. Brendan Eich")
                        ->single();

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    /** @doesNotPerformAssertions */
    public function testCanIndexMapReduceWithFanout(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new FanoutByNumbersWithReduce());

            $session = $store->openSession();
            try {
                $fanout1 = new Fanout();
                $fanout1->setFoo("Foo");
                $fanout1->setNumbers([4, 6, 11, 9]);

                $fanout2 = new Fanout();
                $fanout2->setFoo("Bar");
                $fanout2->setNumbers([3, 8, 5, 17]);

                $session->store($fanout1);
                $session->store($fanout2);
                $session->saveChanges();

                $this->waitForIndexing($store);

                $session->query(FanoutByNumbersWithReduceResult::class, "FanoutByNumbersWithReduce")
                        ->whereEquals("sum", 33)
                        ->single();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    /** @doesNotPerformAssertions */
    public function testCanUseJavaScriptIndexWithDynamicFields(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new UsersByNameAndAnalyzedName());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Brendan Eich");
                $session->store($user);
                $session->saveChanges();

                $this->waitForIndexing($store);

                $session->query(User::class, "UsersByNameAndAnalyzedName")
                        ->selectFields(UsersByNameAndAnalyzedNameResult::class)
                        ->search("analyzedName", "Brendan")
                        ->single();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    /** @doesNotPerformAssertions */
    public function testCanUseJavaScriptMultiMapIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new UsersAndProductsByName());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Brendan Eich");
                $session->store($user);

                $product = new Product();
                $product->setName("Shampoo");
                $product->setAvailable(true);
                $session->store($product);

                $session->saveChanges();

                $this->waitForIndexing($store);

                $session->query(User::class, "UsersAndProductsByName")
                        ->whereEquals("name", "Brendan Eich")
                        ->single();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    /** @doesNotPerformAssertions */
    public function testCanUseJavaScriptMapReduceIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new UsersAndProductsByNameAndCount());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Brendan Eich");
                $session->store($user);

                $product = new Product();
                $product->setName("Shampoo");
                $product->setAvailable(true);
                $session->store($product);

                $session->saveChanges();

                $this->waitForIndexing($store);

                $session->query(User::class, "UsersAndProductsByNameAndCount")
                    ->whereEquals("name", "Brendan Eich")
                    ->single();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testOutputReduceToCollection(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new Products_ByCategory());

            $session = $store->openSession();
            try {
                $category1 = new Category();
                $category1->setName("Beverages");
                $session->store($category1, "categories/1-A");

                $category2 = new Category();
                $category2->setName("Seafood");
                $session->store($category2, "categories/2-A");

                $session->store(Product2::create("categories/1-A", "Lakkalikööri", 13));
                $session->store(Product2::create("categories/1-A", "Original Frankfurter", 16));
                $session->store(Product2::create("categories/2-A", "Röd Kaviar", 18));

                $session->saveChanges();

                $this->waitForIndexing($store);

                $res = $session->query(Products_ByCategoryResult::class, "Products/ByCategory")
                    ->toList();

                $res2 = $session->query(CategoryCount::class)
                    ->toList();

                $this->assertEquals(count($res2), count($res));
                $this->assertGreaterThan(0, count($res2));
                $this->assertGreaterThan(0, count($res));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
