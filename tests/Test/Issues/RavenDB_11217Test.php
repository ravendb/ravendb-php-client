<?php

namespace tests\RavenDB\Test\Issues;

use Exception;
use RavenDB\Documents\Session\QueryStatistics;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Exceptions\IllegalStateException;
use tests\RavenDB\Infrastructure\Orders\Product;
use tests\RavenDB\Infrastructure\Orders\Supplier;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RavenDB_11217Test extends RemoteTestBase
{
    public function testSessionWideNoTrackingShouldWork(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $supplier = new Supplier();
                $supplier->setName("Supplier1");

                $session->store($supplier);

                $product = new Product();
                $product->setName("Product1");
                $product->setSupplier($supplier->getId());

                $session->store($product);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $noTrackingOptions = new SessionOptions();
            $noTrackingOptions->setNoTracking(true);

            $session = $store->openSession($noTrackingOptions);
            try {
                $supplier = new Supplier();
                $supplier->setName("Supplier2");

                try {
                    $session->store($supplier);

                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IllegalStateException::class, $exception);
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession($noTrackingOptions);
            try {
                $this->assertEquals(0, $session->advanced()->getNumberOfRequests());

                $product1 = $session->load(Product::class, "products/1-A", function($b) { $b->includeDocuments("supplier"); });

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertNotNull($product1);

                $this->assertEquals("Product1", $product1->getName());
                $this->assertFalse($session->advanced()->isLoaded($product1->getId()));
                $this->assertFalse($session->advanced()->isLoaded($product1->getSupplier()));

                $supplier = $session->load(Supplier::class, $product1->getSupplier());
                $this->assertNotNull($supplier);
                $this->assertEquals("Supplier1", $supplier->getName());
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertFalse($session->advanced()->isLoaded($supplier->getId()));

                $product2 = $session->load(Product::class, "products/1-A", function($b) { $b->includeDocuments("supplier"); });
                $this->assertNotSame($product1, $product2);
            } finally {
                $session->close();
            }

            $session = $store->openSession($noTrackingOptions);
            try {
                $this->assertEquals(0, $session->advanced()->getNumberOfRequests());

                $product1 = $session
                        ->advanced()
                        ->loadStartingWith(Product::class, "products/")[0];

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $this->assertNotNull($product1);
                $this->assertEquals("Product1", $product1->getName());
                $this->assertFalse($session->advanced()->isLoaded($product1->getId()));
                $this->assertFalse($session->advanced()->isLoaded($product1->getSupplier()));

                $supplier = $session->advanced()->loadStartingWith(Supplier::class, $product1->getSupplier())[0];

                $this->assertNotNull($supplier);

                $this->assertEquals("Supplier1", $supplier->getName());
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertFalse($session->advanced()->isLoaded($supplier->getId()));

                $product2 = $session
                        ->advanced()
                        ->loadStartingWith(Product::class, "products/")[0];

                $this->assertNotSame($product1, $product2);
            } finally {
                $session->close();
            }

            $session = $store->openSession($noTrackingOptions);
            try {
                $this->assertEquals(0, $session->advanced()->getNumberOfRequests());

                $products = $session
                        ->query(Product::class)
                        ->include("supplier")
                        ->toList();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertCount(1, $products);

                $product1 = $products[0];
                $this->assertNotNull($product1);
                $this->assertFalse($session->advanced()->isLoaded($product1->getId()));
                $this->assertFalse($session->advanced()->isLoaded($product1->getSupplier()));

                $supplier = $session->load(Supplier::class, $product1->getSupplier());
                $this->assertNotNull($supplier);
                $this->assertEquals("Supplier1", $supplier->getName());
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertFalse($session->advanced()->isLoaded($supplier->getId()));

                $products = $session
                        ->query(Product::class)
                        ->include("supplier")
                        ->toList();

                $this->assertCount(1, $products);

                $product2 = $products[0];
                $this->assertNotSame($product1, $product2);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->countersFor("products/1-A")->increment("c1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($noTrackingOptions);
            try {
                $product1 = $session->load(Product::class, "products/1-A");
                $counters = $session->countersFor($product1->getId());

                $counters->get("c1");

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $counters->get("c1");

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $val1 = $counters->getAll();

                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                $val2 = $counters->getAll();

                $this->assertEquals(5, $session->advanced()->getNumberOfRequests());

//                $this->assertNotSame($val1, $val2);
//
//                !!! this wouldn't work, in PHP we will always get that this two arrays are same
//                the only way to check that this to values have difference references is to follow next steps>
//                  1. check they are same
//                  2. add something to one array
//                  3. check that arrays are not same

                $this->assertSame($val1, $val2);
                $val1[] = 'add something to that we do not expect to be added in second array';
                $this->assertNotSame($val1, $val2);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSessionWideNoCachingShouldWork(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $stats = new QueryStatistics();
                $session->query(Product::class)
                        ->statistics($stats)
                        ->whereEquals("name", "HR")
                        ->toList();

                $this->assertGreaterThan(0, $stats->getDurationInMs());

                $session->query(Product::class)
                        ->statistics($stats)
                        ->whereEquals("name", "HR")
                        ->toList();

                $this->assertEquals(-1, $stats->getDurationInMs());
            } finally {
                $session->close();
            }

            $noCacheOptions = new SessionOptions();
            $noCacheOptions->setNoCaching(true);

            $session = $store->openSession($noCacheOptions);
            try {
                $stats = new QueryStatistics();
                $session->query(Product::class)
                    ->statistics($stats)
                    ->whereEquals("name", "HR")
                    ->toList();

                $this->assertGreaterThanOrEqual(0, $stats->getDurationInMs());

                $session->query(Product::class)
                        ->statistics($stats)
                        ->whereEquals("name", "HR")
                        ->toList();

                $this->assertGreaterThanOrEqual(0, $stats->getDurationInMs());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
