<?php

namespace tests\RavenDB\Test\Issues\RavenDB_12748Test;

use RavenDB\Documents\Queries\Facets\FacetResult;
use RavenDB\Documents\Queries\Facets\FacetValue;
use RavenDB\Documents\Queries\Facets\RangeBuilder;
use RavenDB\Documents\Queries\Query;
use tests\RavenDB\Infrastructure\Entity\faceted\Currency;
use tests\RavenDB\Infrastructure\Entity\faceted\Order;
use tests\RavenDB\RemoteTestBase;

class RavenDB_12748Test extends RemoteTestBase
{
    public function testCanCorrectlyAggregate(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new Orders_All())->execute($store);

            $session = $store->openSession();
            try {
                $order1 = new Order();
                $order1->setCurrency(Currency::eur());
                $order1->setProduct("Milk");
                $order1->setQuantity(3);
                $order1->setTotal(3);

                $session->store($order1);

                $order2 = new Order();
                $order2->setCurrency(Currency::nis());
                $order2->setProduct("Milk");
                $order2->setQuantity(5);
                $order2->setTotal(9);

                $session->store($order2);

                $order3 = new Order();
                $order3->setCurrency(Currency::eur());
                $order3->setProduct("iPhone");
                $order3->setQuantity(7777);
                $order3->setTotal(3333);

                $session->store($order3);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                /** @var array<FacetResult> $r */
                $r = $session->query(Order::class, Orders_All::class)
                    ->aggregateBy(function ($f) {
                        $f->byField("region");
                    })
                    ->andAggregateBy(function ($f) {
                        $f->byField("product")->sumOn("total")->averageOn("total")->sumOn("quantity");
                    })
                    ->execute();

                /** @var FacetResult $facetResult */
                $facetResult = $r["region"];
                $this->assertCount(1, $facetResult->getValues());
                $this->assertNull($facetResult->getValues()[0]->getName());
                $this->assertEquals(3, $facetResult->getValues()[0]->getCount());

                $facetResult = $r["product"];
                /** @var array<FacetValue> $totalValues */
                $totalValues = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getName() == 'total');
                    }
                );

                $this->assertCount(2, $totalValues);

                /** @var array<FacetValue> $milkValues */
                $milkValues = array_filter(
                    $totalValues,
                    function ($x) {
                        return $x->getRange() == 'milk';
                    }
                );
                $milkValue = $milkValues[array_key_first($milkValues)];

                /** @var array<FacetValue> $iPhoneValues */
                $iPhoneValues = array_filter(
                    $totalValues,
                    function ($x) {
                        return $x->getRange() == 'iphone';
                    }
                );
                $iPhoneValue = $iPhoneValues[array_key_first($iPhoneValues)];

                $this->assertEquals(2, $milkValue->getCount());
                $this->assertEquals(1, $iPhoneValue->getCount());

                $this->assertEquals(12, $milkValue->getSum());
                $this->assertEquals(3333, $iPhoneValue->getSum());

                $this->assertEquals(6, $milkValue->getAverage());
                $this->assertEquals(3333, $iPhoneValue->getAverage());

                $this->assertNull($milkValue->getMax());
                $this->assertNull($iPhoneValue->getMax());

                $this->assertNull($milkValue->getMin());
                $this->assertNull($iPhoneValue->getMin());

                /** @var array<FacetValue> $quantityValues */
                $quantityValues = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return $x->getName() == 'quantity';
                    });

                /** @var array<FacetValue> $milkValues */
                $milkValues = array_filter(
                    $quantityValues,
                    function ($x) {
                        return $x->getRange() == 'milk';
                    }
                );
                $milkValue = $milkValues[array_key_first($milkValues)];

                /** @var array<FacetValue> $iPhoneValues */
                $iPhoneValues = array_filter(
                    $quantityValues,
                    function ($x) {
                        return $x->getRange() == 'iphone';
                    }
                );
                $iPhoneValue = $iPhoneValues[array_key_first($iPhoneValues)];

                $this->assertEquals(2, $milkValue->getCount());
                $this->assertEquals(1, $iPhoneValue->getCount());

                $this->assertEquals(8, $milkValue->getSum());
                $this->assertEquals(7777, $iPhoneValue->getSum());

                $this->assertNull($milkValue->getAverage());
                $this->assertNull($iPhoneValue->getAverage());

                $this->assertNull($milkValue->getMax());
                $this->assertNull($iPhoneValue->getMax());

                $this->assertNull($milkValue->getMin());
                $this->assertNull($iPhoneValue->getMin());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var array<FacetResult> $r */
                $r = $session->query(Order::class, Orders_All::class)
                    ->aggregateBy(function ($f) {
                        $f->byField("region");
                    })
                    ->andAggregateBy(function ($f) {
                        $f->byField("product")->sumOn("total", "T1")->averageOn("total", "T1")->sumOn("quantity", "Q1");
                    })
                    ->execute();

                /** @var FacetResult $facetResult */
                $facetResult = $r["region"];
                $this->assertCount(1, $facetResult->getValues());
                $this->assertNull($facetResult->getValues()[0]->getName());
                $this->assertEquals(3, $facetResult->getValues()[0]->getCount());

                $facetResult = $r["product"];
                /** @var array<FacetValue> $totalValues */
                $totalValues = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getName() == 'T1');
                    }
                );

                $this->assertCount(2, $totalValues);

                /** @var array<FacetValue> $milkValues */
                $milkValues = array_filter(
                    $totalValues,
                    function ($x) {
                        return $x->getRange() == 'milk';
                    }
                );
                $milkValue = $milkValues[array_key_first($milkValues)];

                /** @var array<FacetValue> $iPhoneValues */
                $iPhoneValues = array_filter(
                    $totalValues,
                    function ($x) {
                        return $x->getRange() == 'iphone';
                    }
                );
                $iPhoneValue = $iPhoneValues[array_key_first($iPhoneValues)];

                $this->assertEquals(2, $milkValue->getCount());
                $this->assertEquals(1, $iPhoneValue->getCount());

                $this->assertEquals(12, $milkValue->getSum());
                $this->assertEquals(3333, $iPhoneValue->getSum());

                $this->assertEquals(6, $milkValue->getAverage());
                $this->assertEquals(3333, $iPhoneValue->getAverage());

                $this->assertNull($milkValue->getMax());
                $this->assertNull($iPhoneValue->getMax());

                $this->assertNull($milkValue->getMin());
                $this->assertNull($iPhoneValue->getMin());

                /** @var array<FacetValue> $quantityValues */
                $quantityValues = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return $x->getName() == 'Q1';
                    });

                /** @var array<FacetValue> $milkValues */
                $milkValues = array_filter(
                    $quantityValues,
                    function ($x) {
                        return $x->getRange() == 'milk';
                    }
                );
                $milkValue = $milkValues[array_key_first($milkValues)];

                /** @var array<FacetValue> $iPhoneValues */
                $iPhoneValues = array_filter(
                    $quantityValues,
                    function ($x) {
                        return $x->getRange() == 'iphone';
                    }
                );
                $iPhoneValue = $iPhoneValues[array_key_first($iPhoneValues)];

                $this->assertEquals(2, $milkValue->getCount());
                $this->assertEquals(1, $iPhoneValue->getCount());

                $this->assertEquals(8, $milkValue->getSum());
                $this->assertEquals(7777, $iPhoneValue->getSum());

                $this->assertNull($milkValue->getAverage());
                $this->assertNull($iPhoneValue->getAverage());

                $this->assertNull($milkValue->getMax());
                $this->assertNull($iPhoneValue->getMax());

                $this->assertNull($milkValue->getMin());
                $this->assertNull($iPhoneValue->getMin());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var array<FacetResult> $r */
                $r = $session->query(Order::class, Orders_All::class)
                    ->aggregateBy(function ($f) {
                        $f->byField("region");
                    })
                    ->andAggregateBy(function ($f) {
                        $f->byField("product")
                            ->sumOn("total", "T1")
                            ->sumOn("total", "T2")
                            ->averageOn("total", "T2")
                            ->sumOn("quantity", "Q1");
                    })
                    ->execute();

                /** @var FacetResult $facetResult */
                $facetResult = $r["region"];
                $this->assertCount(1, $facetResult->getValues());
                $this->assertNull($facetResult->getValues()[0]->getName());
                $this->assertEquals(3, $facetResult->getValues()[0]->getCount());

                $facetResult = $r["product"];
                /** @var array<FacetValue> $totalValues */
                $totalValues = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getName() == 'T1');
                    }
                );

                $this->assertCount(2, $totalValues);

                /** @var array<FacetValue> $milkValues */
                $milkValues = array_filter(
                    $totalValues,
                    function ($x) {
                        return $x->getRange() == 'milk';
                    }
                );
                $milkValue = $milkValues[array_key_first($milkValues)];

                /** @var array<FacetValue> $iPhoneValues */
                $iPhoneValues = array_filter(
                    $totalValues,
                    function ($x) {
                        return $x->getRange() == 'iphone';
                    }
                );
                $iPhoneValue = $iPhoneValues[array_key_first($iPhoneValues)];

                $this->assertEquals(2, $milkValue->getCount());
                $this->assertEquals(1, $iPhoneValue->getCount());

                $this->assertEquals(12, $milkValue->getSum());
                $this->assertEquals(3333, $iPhoneValue->getSum());

                $this->assertNull($milkValue->getAverage());
                $this->assertNull($iPhoneValue->getAverage());

                $this->assertNull($milkValue->getMax());
                $this->assertNull($iPhoneValue->getMax());

                $this->assertNull($milkValue->getMin());
                $this->assertNull($iPhoneValue->getMin());

                /** @var array<FacetValue> $totalValues */
                $totalValues = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getName() == 'T2');
                    }
                );

                $this->assertCount(2, $totalValues);

                /** @var array<FacetValue> $milkValues */
                $milkValues = array_filter(
                    $totalValues,
                    function ($x) {
                        return $x->getRange() == 'milk';
                    }
                );
                $milkValue = $milkValues[array_key_first($milkValues)];

                /** @var array<FacetValue> $iPhoneValues */
                $iPhoneValues = array_filter(
                    $totalValues,
                    function ($x) {
                        return $x->getRange() == 'iphone';
                    }
                );
                $iPhoneValue = $iPhoneValues[array_key_first($iPhoneValues)];

                $this->assertEquals(2, $milkValue->getCount());
                $this->assertEquals(1, $iPhoneValue->getCount());

                $this->assertEquals(12, $milkValue->getSum());
                $this->assertEquals(3333, $iPhoneValue->getSum());

                $this->assertEquals(6, $milkValue->getAverage());
                $this->assertEquals(3333, $iPhoneValue->getAverage());

                $this->assertNull($milkValue->getMax());
                $this->assertNull($iPhoneValue->getMax());

                $this->assertNull($milkValue->getMin());
                $this->assertNull($iPhoneValue->getMin());

                /** @var array<FacetValue> $quantityValues */
                $quantityValues = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return $x->getName() == 'Q1';
                    });

                /** @var array<FacetValue> $milkValues */
                $milkValues = array_filter(
                    $quantityValues,
                    function ($x) {
                        return $x->getRange() == 'milk';
                    }
                );
                $milkValue = $milkValues[array_key_first($milkValues)];

                /** @var array<FacetValue> $iPhoneValues */
                $iPhoneValues = array_filter(
                    $quantityValues,
                    function ($x) {
                        return $x->getRange() == 'iphone';
                    }
                );
                $iPhoneValue = $iPhoneValues[array_key_first($iPhoneValues)];

                $this->assertEquals(2, $milkValue->getCount());
                $this->assertEquals(1, $iPhoneValue->getCount());

                $this->assertEquals(8, $milkValue->getSum());
                $this->assertEquals(7777, $iPhoneValue->getSum());

                $this->assertNull($milkValue->getAverage());
                $this->assertNull($iPhoneValue->getAverage());

                $this->assertNull($milkValue->getMax());
                $this->assertNull($iPhoneValue->getMax());

                $this->assertNull($milkValue->getMin());
                $this->assertNull($iPhoneValue->getMin());
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
                $order1 = new Order();
                $order1->setCurrency(Currency::eur());
                $order1->setProduct("Milk");
                $order1->setQuantity(3);
                $order1->setTotal(3);

                $session->store($order1);

                $order2 = new Order();
                $order2->setCurrency(Currency::nis());
                $order2->setProduct("Milk");
                $order2->setQuantity(5);
                $order2->setTotal(9);

                $session->store($order2);

                $order3 = new Order();
                $order3->setCurrency(Currency::eur());
                $order3->setProduct("iPhone");
                $order3->setQuantity(7777);
                $order3->setTotal(3333);

                $session->store($order3);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $range = RangeBuilder::forPath("total");

                $r = $session->query(Order::class, Query::index("Orders/All"))
                    ->aggregateBy(function ($f) use ($range) {
                        $f->byRanges(
                            $range->isLessThan(100),
                            $range->isGreaterThanOrEqualTo(100)->isLessThan(500),
                            $range->isGreaterThanOrEqualTo(500)->isLessThan(1500),
                            $range->isGreaterThanOrEqualTo(1500)
                        )
                            ->sumOn("total")
                            ->averageOn("total")
                            ->sumOn("quantity");
                    })
                    ->andAggregateBy(function ($f) {
                        $f->byField("product")->sumOn("total")->averageOn("total")->sumOn("quantity");
                    })
                    ->execute();

                /** @var FacetResult $facetResult */
                $facetResult = $r["total"];
                $this->assertCount(8, $facetResult->getValues());

                /** @var array<FacetValue> $range1Values */
                $range1Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total < 100') && $x->getName() == 'total';
                    }
                );
                $range1 = $range1Values[array_key_first($range1Values)];

                /** @var array<FacetValue> $range2Values */
                $range2Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total >= 1500') && $x->getName() == 'total';
                    }
                );
                $range2 = $range2Values[array_key_first($range2Values)];

                $this->assertEquals(2, $range1->getCount());
                $this->assertEquals(1, $range2->getCount());

                $this->assertEquals(12, $range1->getSum());
                $this->assertEquals(3333, $range2->getSum());

                $this->assertEquals(6, $range1->getAverage());
                $this->assertEquals(3333, $range2->getAverage());

                $this->assertNull($range1->getMax());
                $this->assertNull($range2->getMax());

                $this->assertNull($range1->getMin());
                $this->assertNull($range2->getMin());

                /** @var array<FacetValue> $range1Values */
                $range1Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total < 100') && $x->getName() == 'quantity';
                    }
                );
                $range1 = $range1Values[array_key_first($range1Values)];

                /** @var array<FacetValue> $range2Values */
                $range2Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total >= 1500') && $x->getName() == 'quantity';
                    }
                );
                $range2 = $range2Values[array_key_first($range2Values)];

                $this->assertEquals(2, $range1->getCount());
                $this->assertEquals(1, $range2->getCount());

                $this->assertEquals(8, $range1->getSum());
                $this->assertEquals(7777, $range2->getSum());

                $this->assertNull($range1->getAverage());
                $this->assertNull($range2->getAverage());

                $this->assertNull($range1->getMax());
                $this->assertNull($range2->getMax());

                $this->assertNull($range1->getMin());
                $this->assertNull($range2->getMin());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $range = RangeBuilder::forPath("total");

                $r = $session->query(Order::class, Query::index("Orders/All"))
                    ->aggregateBy(function ($f) use ($range) {
                        $f->byRanges(
                            $range->isLessThan(100),
                            $range->isGreaterThanOrEqualTo(100)->isLessThan(500),
                            $range->isGreaterThanOrEqualTo(500)->isLessThan(1500),
                            $range->isGreaterThanOrEqualTo(1500)
                        )
                            ->sumOn("total", "T1")
                            ->averageOn("total", "T1")
                            ->sumOn("quantity", "Q1");
                    })
                    ->andAggregateBy(function ($f) {
                        $f->byField("product")->sumOn("total")->averageOn("total")->sumOn("quantity");
                    })
                    ->execute();

                /** @var FacetResult $facetResult */
                $facetResult = $r["total"];
                $this->assertCount(8, $facetResult->getValues());

                /** @var array<FacetValue> $range1Values */
                $range1Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total < 100') && $x->getName() == 'T1';
                    }
                );
                $range1 = $range1Values[array_key_first($range1Values)];

                /** @var array<FacetValue> $range2Values */
                $range2Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total >= 1500') && $x->getName() == 'T1';
                    }
                );
                $range2 = $range2Values[array_key_first($range2Values)];

                $this->assertEquals(2, $range1->getCount());
                $this->assertEquals(1, $range2->getCount());

                $this->assertEquals(12, $range1->getSum());
                $this->assertEquals(3333, $range2->getSum());

                $this->assertEquals(6, $range1->getAverage());
                $this->assertEquals(3333, $range2->getAverage());

                $this->assertNull($range1->getMax());
                $this->assertNull($range2->getMax());

                $this->assertNull($range1->getMin());
                $this->assertNull($range2->getMin());


                /** @var array<FacetValue> $range1Values */
                $range1Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total < 100') && $x->getName() == 'Q1';
                    }
                );
                $range1 = $range1Values[array_key_first($range1Values)];

                /** @var array<FacetValue> $range2Values */
                $range2Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total >= 1500') && $x->getName() == 'Q1';
                    }
                );
                $range2 = $range2Values[array_key_first($range2Values)];

                $this->assertEquals(2, $range1->getCount());
                $this->assertEquals(1, $range2->getCount());

                $this->assertEquals(8, $range1->getSum());
                $this->assertEquals(7777, $range2->getSum());

                $this->assertNull($range1->getAverage());
                $this->assertNull($range2->getAverage());

                $this->assertNull($range1->getMax());
                $this->assertNull($range2->getMax());

                $this->assertNull($range1->getMin());
                $this->assertNull($range2->getMin());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $range = RangeBuilder::forPath("total");

                $r = $session->query(Order::class, Query::index("Orders/All"))
                    ->aggregateBy(function ($f) use ($range) {
                        $f->byRanges(
                            $range->isLessThan(100),
                            $range->isGreaterThanOrEqualTo(100)->isLessThan(500),
                            $range->isGreaterThanOrEqualTo(500)->isLessThan(1500),
                            $range->isGreaterThanOrEqualTo(1500)
                        )
                            ->sumOn("total", "T1")
                            ->sumOn("total", "T2")
                            ->averageOn("total", "T2")
                            ->sumOn("quantity", "Q1");
                    })
                    ->andAggregateBy(function ($f) {
                        $f->byField("product")->sumOn("total")->averageOn("total")->sumOn("quantity");
                    })
                    ->execute();

                /** @var FacetResult $facetResult */
                $facetResult = $r["total"];
                $this->assertCount(12, $facetResult->getValues());

                /** @var array<FacetValue> $range1Values */
                $range1Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total < 100') && $x->getName() == 'T1';
                    }
                );
                $range1 = $range1Values[array_key_first($range1Values)];

                /** @var array<FacetValue> $range2Values */
                $range2Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total >= 1500') && $x->getName() == 'T1';
                    }
                );
                $range2 = $range2Values[array_key_first($range2Values)];

                $this->assertEquals(2, $range1->getCount());
                $this->assertEquals(1, $range2->getCount());

                $this->assertEquals(12, $range1->getSum());
                $this->assertEquals(3333, $range2->getSum());

                $this->assertNull($range1->getAverage());
                $this->assertNull($range2->getAverage());

                $this->assertNull($range1->getMax());
                $this->assertNull($range2->getMax());

                $this->assertNull($range1->getMin());
                $this->assertNull($range2->getMin());

                /** @var array<FacetValue> $range1Values */
                $range1Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total < 100') && $x->getName() == 'T2';
                    }
                );
                $range1 = $range1Values[array_key_first($range1Values)];

                /** @var array<FacetValue> $range2Values */
                $range2Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total >= 1500') && $x->getName() == 'T2';
                    }
                );
                $range2 = $range2Values[array_key_first($range2Values)];

                $this->assertEquals(2, $range1->getCount());
                $this->assertEquals(1, $range2->getCount());

                $this->assertEquals(12, $range1->getSum());
                $this->assertEquals(3333, $range2->getSum());

                $this->assertEquals(6, $range1->getAverage());
                $this->assertEquals(3333, $range2->getAverage());

                $this->assertNull($range1->getMax());
                $this->assertNull($range2->getMax());

                $this->assertNull($range1->getMin());
                $this->assertNull($range2->getMin());

                /** @var array<FacetValue> $range1Values */
                $range1Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total < 100') && $x->getName() == 'Q1';
                    }
                );
                $range1 = $range1Values[array_key_first($range1Values)];

                /** @var array<FacetValue> $range2Values */
                $range2Values = array_filter(
                    $facetResult->getValues()->getArrayCopy(),
                    function ($x) {
                        return ($x->getRange() == 'total >= 1500') && $x->getName() == 'Q1';
                    }
                );
                $range2 = $range2Values[array_key_first($range2Values)];

                $this->assertEquals(2, $range1->getCount());
                $this->assertEquals(1, $range2->getCount());

                $this->assertEquals(8, $range1->getSum());
                $this->assertEquals(7777, $range2->getSum());

                $this->assertNull($range1->getAverage());
                $this->assertNull($range2->getAverage());

                $this->assertNull($range1->getMax());
                $this->assertNull($range2->getMax());

                $this->assertNull($range1->getMin());
                $this->assertNull($range2->getMin());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
