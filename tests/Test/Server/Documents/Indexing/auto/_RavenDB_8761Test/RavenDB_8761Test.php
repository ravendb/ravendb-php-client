<?php

namespace tests\RavenDB\Test\Server\Documents\Indexing\auto\_RavenDB_8761Test;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Queries\GroupBy;
use tests\RavenDB\Infrastructure\Entity\Address;
use tests\RavenDB\Infrastructure\Entity\Order;
use tests\RavenDB\Infrastructure\Entity\OrderLine;
use tests\RavenDB\Infrastructure\Entity\OrderLineList;
use tests\RavenDB\RemoteTestBase;

/** !status: DONE */
class RavenDB_8761Test extends RemoteTestBase
{
    public function testCan_group_by_array_values(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->putDocs($store);

            $session = $store->openSession();
            try {
                /** @var array<ProductCount> $productCounts1 */
                $productCounts1 = $session->advanced()
                    ->rawQuery(ProductCount::class, "from Orders group by lines[].product\n" .
                        "  order by count()\n" .
                        "  select key() as productName, count() as count")
                    ->waitForNonStaleResults()
                    ->toList();

                /** @var array<ProductCount> $productCounts2 */
                $productCounts2 = $session->advanced()
                    ->documentQuery(Order::class)
                    ->groupBy("lines[].product")
                    ->selectKey(null, "productName")
                    ->selectCount()
                    ->ofType(ProductCount::class)
                    ->toList();


                foreach ([$productCounts1, $productCounts2] as $products) {
                    $this->assertCount(2, $products);

                    $this->assertEquals("products/1", $products[0]->getProductName());
                    $this->assertEquals(1, $products[0]->getCount());

                    $this->assertEquals("products/2", $products[1]->getProductName());
                    $this->assertEquals(2, $products[1]->getCount());
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var array<ProductCount> $productCounts1 */
                $productCounts1 = $session->advanced()
                    ->rawQuery(ProductCount::class, "from Orders\n" .
                        " group by lines[].product, shipTo.country\n" .
                        " order by count() \n" .
                        " select lines[].product as productName, shipTo.country as country, count() as count")
                    ->toList();

                /** @var array<ProductCount> $productCounts2 */
                $productCounts2 = $session->advanced()
                    ->documentQuery(Order::class)
                    ->groupBy("lines[].product", "shipTo.country")
                    ->selectKey("lines[].product", "productName")
                    ->selectKey("shipTo.country", "country")
                    ->selectCount()
                    ->ofType(ProductCount::class)
                    ->toList();

                foreach ([$productCounts1, $productCounts2] as $products) {
                    $this->assertCount(2, $products);

                    $this->assertEquals("products/1", $products[0]->getProductName());
                    $this->assertEquals(1, $products[0]->getCount());
                    $this->assertEquals("USA", $products[0]->getCountry());

                    $this->assertEquals("products/2", $products[1]->getProductName());
                    $this->assertEquals(2, $products[1]->getCount());
                    $this->assertEquals("USA", $products[1]->getCountry());
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var array<ProductCount> $productCounts1 */
                $productCounts1 = $session->advanced()
                    ->rawQuery(ProductCount::class, "from Orders\n" .
                        " group by lines[].product, lines[].quantity\n" .
                        " order by lines[].quantity\n" .
                        " select lines[].product as productName, lines[].quantity as quantity, count() as count")
                    ->toList();


                /** @var array<ProductCount> $productCounts2 */
                $productCounts2 = $session->advanced()
                    ->documentQuery(Order::class)
                    ->groupBy("lines[].product", "lines[].quantity")
                    ->selectKey("lines[].product", "productName")
                    ->selectKey("lines[].quantity", "quantity")
                    ->selectCount()
                    ->ofType(ProductCount::class)
                    ->toList();

                foreach ([$productCounts1, $productCounts2] as $products) {
                    $this->assertCount(3, $products);

                    $this->assertEquals("products/1", $products[0]->getProductName());
                    $this->assertEquals(1, $products[0]->getCount());
                    $this->assertEquals(1, $products[0]->getQuantity());

                    $this->assertEquals("products/2", $products[1]->getProductName());
                    $this->assertEquals(1, $products[1]->getCount());
                    $this->assertEquals(2, $products[1]->getQuantity());

                    $this->assertEquals("products/2", $products[2]->getProductName());
                    $this->assertEquals(1, $products[2]->getCount());
                    $this->assertEquals(3, $products[2]->getQuantity());
                }

                $this->assertTrue(true);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCan_group_by_array_content(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->putDocs($store);

            $session = $store->openSession();
            try {
                $orderLine1 = new OrderLine();
                $orderLine1->setProduct("products/1");
                $orderLine1->setQuantity(1);

                $orderLine2 = new OrderLine();
                $orderLine2->setProduct("products/2");
                $orderLine2->setQuantity(2);

                $address = new Address();
                $address->setCountry("USA");

                $order = new Order();
                $order->setShipTo($address);
                $order->setLines(OrderLineList::fromArray([$orderLine1, $orderLine2]));

                $session->store($order);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var array<ProductCount> $productCounts1 */
                $productCounts1 = $session->advanced()
                    ->rawQuery(ProductCount::class, "from Orders group by array(lines[].product)\n" .
                        " order by count()\n" .
                        " select key() as products, count() as count")
                    ->waitForNonStaleResults()
                    ->toList();

                /** @var array<ProductCount> $productCounts2 */
                $productCounts2 = $session->advanced()
                    ->documentQuery(Order::class)
                    ->groupBy(GroupBy::array("lines[].product"))
                    ->selectKey(null, "products")
                    ->selectCount()
                    ->orderBy("count")
                    ->ofType(ProductCount::class)
                    ->toList();

                foreach ([$productCounts1, $productCounts2] as $products) {
                    $this->assertCount(2, $products);

                    $this->assertCount(1, $products[0]->getProducts());
                    $this->assertContains("products/2", $products[0]->getProducts());
                    $this->assertEquals(1, $products[0]->getCount());

                    $this->assertCount(2, $products[1]->getProducts());
                    $this->assertEquals(["products/1", "products/2"], $products[1]->getProducts());
                    $this->assertEquals(2, $products[1]->getCount());
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var array<ProductCount> $productCounts1 */
                $productCounts1 = $session->advanced()->rawQuery(ProductCount::class, "from Orders\n" .
                    " group by array(lines[].product), shipTo.country\n" .
                    " order by count()\n" .
                    " select lines[].product as products, shipTo.country as country, count() as count")
                    ->waitForNonStaleResults()
                    ->toList();

                /** @ var array<ProductCount> $productCounts2 */
                $productCounts2 = $session
                    ->advanced()
                    ->documentQuery(Order::class)
                    ->groupBy(GroupBy::array("lines[].product"), GroupBy::field("shipTo.country"))
                    ->selectKey("lines[].product", "products")
                    ->selectCount()
                    ->orderBy("count")
                    ->ofType(ProductCount::class)
                    ->toList();

                foreach ([$productCounts1, $productCounts2] as $products) {
                    $this->assertCount(2, $products);

                    $this->assertCount(1, $products[0]->getProducts());
                    $this->assertContains("products/2", $products[0]->getProducts());
                    $this->assertEquals(1, $products[0]->getCount());

                    $this->assertCount(2, $products[1]->getProducts());
                    $this->assertEquals(["products/1", "products/2"], $products[1]->getProducts());
                    $this->assertEquals(2, $products[1]->getCount());
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var array<ProductCount> $productCounts1 */
                $productCounts1 = $session->advanced()->rawQuery(ProductCount::class, "from Orders\n" .
                    " group by array(lines[].product), array(lines[].quantity)\n" .
                    " order by count()\n" .
                    " select lines[].product as products, lines[].quantity as quantities, count() as count")
                    ->waitForNonStaleResults()
                    ->toList();

                /** @ var array<ProductCount> $productCounts2 */
                $productCounts2 = $session
                    ->advanced()
                        ->documentQuery(Order::class)
                        ->groupBy(GroupBy::array("lines[].product"), GroupBy::array("lines[].quantity"))
                        ->selectKey("lines[].product", "products")
                        ->selectKey("lines[].quantity", "quantities")
                        ->selectCount()
                        ->orderBy("count")
                        ->ofType(ProductCount::class)
                        ->toList();

                foreach ([$productCounts1, $productCounts2] as $products) {
                    $this->assertCount(2, $products);

                    $this->assertCount(1, $products[0]->getProducts());
                    $this->assertContains("products/2", $products[0]->getProducts());
                    $this->assertEquals(1, $products[0]->getCount());
                    $this->assertEquals([3], $products[0]->getQuantities());

                    $this->assertCount(2, $products[1]->getProducts());
                    $this->assertEquals(["products/1", "products/2"], $products[1]->getProducts());
                    $this->assertEquals(2, $products[1]->getCount());
                    $this->assertEquals([1,2], $products[1]->getQuantities());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    private function putDocs(DocumentStoreInterface $store): void
    {
        $session = $store->openSession();
        try {
            $order1 = new Order();

            $orderLine11 = new OrderLine();
            $orderLine11->setProduct("products/1");
            $orderLine11->setQuantity(1);

            $orderLine12 = new OrderLine();
            $orderLine12->setProduct("products/2");
            $orderLine12->setQuantity(2);

            $order1->setLines(OrderLineList::fromArray([$orderLine11, $orderLine12]));

            $address1 = new Address();
            $address1->setCountry("USA");

            $order1->setShipTo($address1);

            $session->store($order1);

            $orderLine21 = new OrderLine();
            $orderLine21->setProduct("products/2");
            $orderLine21->setQuantity(3);

            $address2 = new Address();
            $address2->setCountry("USA");
            $order2 = new Order();
            $order2->setLines(OrderLineList::fromArray([$orderLine21]));
            $order2->setShipTo($address2);
            $session->store($order2);

            $session->saveChanges();
        } finally {
            $session->close();
        }
    }
}
