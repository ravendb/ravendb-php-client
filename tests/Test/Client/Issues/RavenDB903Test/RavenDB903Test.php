<?php

namespace tests\RavenDB\Test\Client\Issues\RavenDB903Test;

use RavenDB\Documents\Session\DocumentQueryInterface;
use RavenDB\Documents\Session\DocumentSessionInterface;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\Issues\RavenDB903Test\Entity\Product;
use tests\RavenDB\Test\Client\Issues\RavenDB903Test\Entity\TestIndex;

class RavenDB903Test extends RemoteTestBase
{
    public function test1(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $this->doTest(
            function (DocumentSessionInterface $session): DocumentQueryInterface {
                return $session->advanced()->documentQuery(Product::class, TestIndex::class)
                    ->search('description', "Hello")
                    ->intersect()
                    ->whereEquals("name", "bar");
            }
        );
    }

    public function test2(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $this->doTest(
            function (DocumentSessionInterface $session): DocumentQueryInterface {
                return $session->advanced()->documentQuery(Product::class, TestIndex::class)
                    ->search('name', "Bar")
                    ->intersect()
                    ->whereEquals("description", "Hello");
            }
        );
    }

    private function doTest($queryFunction): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new TestIndex());

            $session = $store->openSession();
            try {
                $product1 = new Product();
                $product1->setName("Foo");
                $product1->setDescription("Hello World");

                $product2 = new Product();
                $product2->setName("Bar");
                $product2->setDescription("Hello World");

                $product3 = new Product();
                $product3->setName("Bar");
                $product3->setDescription("Goodbye World");

                $session->store($product1);
                $session->store($product2);
                $session->store($product3);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $query = $queryFunction($session);

                $products = $query->toList();
                $this->assertCount(1, $products);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
