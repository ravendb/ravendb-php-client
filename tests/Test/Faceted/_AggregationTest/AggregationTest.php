<?php

namespace tests\RavenDB\Test\Faceted\_AggregationTest;

use RavenDB\Documents\Queries\Facets\FacetResult;
use RavenDB\Documents\Queries\Facets\RangeBuilder;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\RemoteTestBase;

class AggregationTest extends RemoteTestBase
{
    public function testCanCorrectlyAggregate_Double(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new Orders_All())->execute($store);

            $session = $store->openSession();
            try {
                $obj = new Order();
                $obj->setCurrency(Currency::eur());
                $obj->setProduct("Milk");
                $obj->setTotal(1.1);
                $obj->setRegion(1);

                $obj2 = new Order();
                $obj2->setCurrency(Currency::eur());
                $obj2->setProduct("Milk");
                $obj2->setTotal(1);
                $obj2->setRegion(1);

                $session->store($obj);
                $session->store($obj2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                 /** @var array<FacetResult> $result */
                $result = $session
                        ->query(Order::class, Orders_All::class)
                        ->aggregateBy(function($f) { $f->byField("region")
                                ->maxOn("total")
                                ->minOn("total");
                            })
                        ->execute();

                /** @var FacetResult  $facetResult */
                $facetResult = $result["region"];

                $this->assertEquals(2, $facetResult->getValues()[0]->getCount());
                $this->assertEquals(1, $facetResult->getValues()[0]->getMin());
                $this->assertEquals(1.1, $facetResult->getValues()[0]->getMax());
                $this->assertCount(1, array_filter($facetResult->getValues()->getArrayCopy(), function($x) { return $x->getrange() == "1";}));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanCorrectlyAggregate_MultipleItems(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new Orders_All())->execute($store);

            $session = $store->openSession();
            try {
                $obj = new Order();
                $obj->setCurrency(Currency::eur());
                $obj->setProduct("Milk");
                $obj->setTotal(3);

                $obj2 = new Order();
                $obj2->setCurrency(Currency::nis());
                $obj2->setProduct("Milk");
                $obj2->setTotal(9);

                $obj3 = new Order();
                $obj3->setCurrency(Currency::eur());
                $obj3->setProduct("iPhone");
                $obj3->setTotal(3333);

                $session->store($obj);
                $session->store($obj2);
                $session->store($obj3);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                /** @var array<FacetResult> $r */
                $r = $session
                    ->query(Order::class, Orders_All::class)
                    ->aggregateBy(function($x) { $x->byField("product")->sumOn("total");})
                    ->andAggregateBy(function($x) { $x->byField("currency")->sumOn("total");})
                    ->execute();

                /** @var FacetResult  $facetResult */
                $facetResult = $r["product"];
                $this->assertCount(2, $facetResult->getValues());

                $milkValues = array_filter($facetResult->getValues()->getArrayCopy(), function($x) { return $x->getRange() == "milk"; });
                $milkValue = $milkValues[array_key_first($milkValues)];
                $this->assertEquals(12, $milkValue->getSum());

                $iphoneValues = array_filter($facetResult->getValues()->getArrayCopy(), function($x) { return $x->getRange() == "iphone"; });
                $iphoneValue = $iphoneValues[array_key_first($iphoneValues)];
                $this->assertEquals(3333, $iphoneValue->getSum());

                /** @var FacetResult  $facetResult */
                $facetResult = $r["currency"];
                $this->assertCount(2, $facetResult->getValues());

                $facetResult = $r["currency"];
                $this->assertCount(2, $facetResult->getValues());

                $eurValues = array_filter($facetResult->getValues()->getArrayCopy(), function($x) { return $x->getRange() == "eur"; });
                $eurValue = $eurValues[array_key_first($eurValues)];
                $this->assertEquals(3336, $eurValue->getSum());

                $nisValues = array_filter($facetResult->getValues()->getArrayCopy(), function($x) { return $x->getRange() == "nis"; });
                $nisValue = $nisValues[array_key_first($nisValues)];
                $this->assertEquals(9, $nisValue->getSum());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanCorrectlyAggregate_MultipleAggregations(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new Orders_All())->execute($store);

            $session = $store->openSession();
            try {
                $obj = new Order();
                $obj->setCurrency(Currency::eur());
                $obj->setProduct("Milk");
                $obj->setTotal(3);

                $obj2 = new Order();
                $obj2->setCurrency(Currency::nis());
                $obj2->setProduct("Milk");
                $obj2->setTotal(9);

                $obj3 = new Order();
                $obj3->setCurrency(Currency::eur());
                $obj3->setProduct("iPhone");
                $obj3->setTotal(3333);

                $session->store($obj);
                $session->store($obj2);
                $session->store($obj3);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                /** @var array<FacetResult> $r */
                $r = $session
                    ->query(Order::class, Orders_All::class)
                    ->aggregateBy(function($x) { $x->byField("product")->maxOn("total")->minOn("total");})
                    ->execute();

                /** @var FacetResult  $facetResult */
                $facetResult = $r["product"];
                $this->assertCount(2, $facetResult->getValues());

                $milkValues = array_filter($facetResult->getValues()->getArrayCopy(), function($x) { return $x->getRange() == "milk"; });
                $milkValue = $milkValues[array_key_first($milkValues)];
                $this->assertEquals(9, $milkValue->getMax());
                $this->assertEquals(3, $milkValue->getMin());

                $iphoneValues = array_filter($facetResult->getValues()->getArrayCopy(), function($x) { return $x->getRange() == "iphone"; });
                $iphoneValue = $iphoneValues[array_key_first($iphoneValues)];
                $this->assertEquals(3333, $iphoneValue->getMin());
                $this->assertEquals(3333, $iphoneValue->getMax());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanCorrectlyAggregate_DisplayName(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new Orders_All())->execute($store);

            $session = $store->openSession();
            try {
                $obj = new Order();
                $obj->setCurrency(Currency::eur());
                $obj->setProduct("Milk");
                $obj->setTotal(3);

                $obj2 = new Order();
                $obj2->setCurrency(Currency::nis());
                $obj2->setProduct("Milk");
                $obj2->setTotal(9);

                $obj3 = new Order();
                $obj3->setCurrency(Currency::eur());
                $obj3->setProduct("iPhone");
                $obj3->setTotal(3333);

                $session->store($obj);
                $session->store($obj2);
                $session->store($obj3);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                /** @var array<FacetResult> $r */
                $r = $session
                    ->query(Order::class, Orders_All::class)
                    ->aggregateBy(function($x) { $x->byField("product")->withDisplayName("productMax")->maxOn("total");})
                    ->andAggregateBy(function($x) { $x->byField("product")->withDisplayName("productMin");})
                    ->execute();

                $this->assertCount(2, $r);

                $this->assertNotNull($r['productMax']);
                $this->assertNotNull($r['productMin']);

                $this->assertEquals(3333, $r['productMax']->getValues()[0]->getMax());
                $this->assertEquals(2, $r['productMin']->getValues()[1]->getCount());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanCorrectlyAggregate_Ranges(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new Orders_All())->execute($store);

            $session = $store->openSession();
            try {
                $obj = new Order();
                $obj->setCurrency(Currency::eur());
                $obj->setProduct("Milk");
                $obj->setTotal(3);

                $obj2 = new Order();
                $obj2->setCurrency(Currency::nis());
                $obj2->setProduct("Milk");
                $obj2->setTotal(9);

                $obj3 = new Order();
                $obj3->setCurrency(Currency::eur());
                $obj3->setProduct("iPhone");
                $obj3->setTotal(3333);

                $session->store($obj);
                $session->store($obj2);
                $session->store($obj3);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $range = RangeBuilder::forPath("total");
                /** @var array<FacetResult> $r */
                $r = $session
                    ->query(Order::class, Orders_All::class)
                    ->aggregateBy(function($x) { $x->byField("product")->sumOn("total");})
                    ->andAggregateBy(function($x) use ($range) {
                        $x->byRanges(
                                $range->isLessThan(100),
                                $range->isGreaterThanOrEqualTo(100)->isLessThan(500),
                                $range->isGreaterThanOrEqualTo(500)->isLessThan(1500),
                                $range->isGreaterThanOrEqualTo(1500)
                        )->sumOn("total");
                    })
                    ->execute();

                /** @var FacetResult $facetResult */
                $facetResult = $r["product"];
                $this->assertCount(2, $facetResult->getValues());

                $milkValues = array_filter($facetResult->getValues()->getArrayCopy(), function($x) { return $x->getRange() == "milk"; });
                $milkValue = $milkValues[array_key_first($milkValues)];
                $this->assertEquals(12, $milkValue->getSum());

                $iphoneValues = array_filter($facetResult->getValues()->getArrayCopy(), function($x) { return $x->getRange() == "iphone"; });
                $iphoneValue = $iphoneValues[array_key_first($iphoneValues)];
                $this->assertEquals(3333, $iphoneValue->getSum());

                $facetResult = $r["total"];
                $this->assertCount(4, $facetResult->getValues());

                $rangeValues = array_filter($facetResult->getValues()->getArrayCopy(), function($x) { return $x->getRange() == "total < 100"; });
                $rangeValue = $rangeValues[array_key_first($rangeValues)];
                $this->assertEquals(12, $rangeValue->getSum());

                $rangeValues = array_filter($facetResult->getValues()->getArrayCopy(), function($x) { return $x->getRange() == "total >= 1500"; });
                $rangeValue = $rangeValues[array_key_first($rangeValues)];
                $this->assertEquals(3333, $rangeValue->getSum());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    /**
     * @throws \Exception
     */
    public function testCanCorrectlyAggregate_DateTimeDataType_WithRangeCounts(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new ItemsOrders_All())->execute($store);

            $session = $store->openSession();
            try {
                $item1 = new ItemsOrder();
                $item1->setItems(["first", "second"]);
                $item1->setAt(DateUtils::now());

                $item2 = new ItemsOrder();
                $item2->setItems(["first", "second"]);
                $item2->setAt(DateUtils::addDays(DateUtils::now(), -1));

                $item3 = new ItemsOrder();
                $item3->setItems(["first", "second"]);
                $item3->setAt(DateUtils::now());

                $item4 = new ItemsOrder();
                $item4->setItems(["first"]);
                $item4->setAt(DateUtils::now());

                $session->store($item1);
                $session->store($item2);
                $session->store($item3);
                $session->store($item4);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $items = ["second"];

            $minValue = DateUtils::setYears(DateUtils::now(), 1980);

            $end0 = DateUtils::addDays(DateUtils::now(), -2);
            $end1 = DateUtils::addDays(DateUtils::now(), -1);
            $end2 = DateUtils::now();


            $this->waitForIndexing($store);

            $builder = RangeBuilder::forPath("at");
            $session = $store->openSession();
            try {
                /** @var array<FacetResult> $r */
                $r = $session
                    ->query(ItemsOrder::class, ItemsOrders_All::class)
                    ->whereGreaterThanOrEqual("at", $end0)
                    ->aggregateBy(function($x) use ($builder, $minValue, $end0, $end1, $end2) {
                        $x->byRanges(
                            $builder->isGreaterThanOrEqualTo($minValue), // all - 4
                            $builder->isGreaterThanOrEqualTo($end0)->isLessThan($end1), // 0
                            $builder->isGreaterThanOrEqualTo($end1)->isLessThan($end2) // 1
                        );
                    })
                    ->execute();

                $facetResults = $r['at']->getValues();

                $this->assertEquals(4, $facetResults[0]->getCount());
                $this->assertEquals(1, $facetResults[1]->getCount());
                $this->assertEquals(3, $facetResults[2]->getCount());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
