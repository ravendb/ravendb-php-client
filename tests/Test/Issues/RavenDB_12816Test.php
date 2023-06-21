<?php

namespace tests\RavenDB\Test\Issues;

use Exception;
use RavenDB\Documents\Queries\Facets\Facet;
use RavenDB\Documents\Queries\Facets\FacetSetup;
use RavenDB\Documents\Queries\Facets\RangeFacet;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\_FacetTestBase\Camera;
use tests\RavenDB\Test\_FacetTestBase\CameraCostIndex;
use Throwable;

class RavenDB_12816Test extends RemoteTestBase
{
    public function testCanSendFacetedRawQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $index = new CameraCostIndex();
            $index->execute($store);

            $session = $store->openSession();
            try {
                for ($i = 0; $i < 10; $i++) {
                    $camera = new Camera();
                    $camera->setId("cameras/" . $i);
                    $camera->setManufacturer($i % 2 == 0 ? "Manufacturer1" : "Manufacturer2");
                    $camera->setCost($i * 100);
                    $camera->setMegapixels($i);
                    $session->store($camera);
                }
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $facets = [];

            $facet1 = new Facet();
            $facet1->setFieldName("manufacturer");

            $facets[] = $facet1;

            $rangeFacets = [];

            $rangeFacet1 = new RangeFacet();
            $rangeFacet1->setRanges([
                "cost <= 200",
                "cost >= 300 and cost <= 400",
                "cost >= 500 and cost <= 600",
                "cost >= 700 and cost <= 800",
                "cost >= 900"
            ]);

            $rangeFacet2 = new RangeFacet();
            $rangeFacet2->setRanges([
                "megapixels <= 3",
                "megapixels >= 4 and megapixels <= 7",
                "megapixels >= 8 and megapixels <= 10",
                "megapixels >= 11"
            ]);

            $rangeFacets[] = $rangeFacet1;
            $rangeFacets[] = $rangeFacet2;

            $session = $store->openSession();
            try {
                $facetSetup = new FacetSetup();
                $facetSetup->setId("facets/CameraFacets");
                $facetSetup->setFacets($facets);
                $facetSetup->setRangeFacets($rangeFacets);
                $session->store($facetSetup);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $facetResults = $session
                        ->advanced()
                        ->rawQuery(Camera::class, "from index 'CameraCost' select facet(id('facets/CameraFacets'))")
                        ->executeAggregation();

                $this->assertCount(3, $facetResults);

                $this->assertCount(2, $facetResults["manufacturer"]->getValues());

                $this->assertEquals("manufacturer1", $facetResults["manufacturer"]->getValues()[0]->getRange());
                $this->assertEquals(5, $facetResults["manufacturer"]->getValues()[0]->getCount());
                $this->assertEquals("manufacturer2", $facetResults["manufacturer"]->getValues()[1]->getRange());
                $this->assertEquals(5, $facetResults["manufacturer"]->getValues()[1]->getCount());

                $this->assertCount(5, $facetResults["cost"]->getValues());

                $this->assertEquals("cost <= 200", $facetResults["cost"]->getValues()[0]->getRange());
                $this->assertEquals(3, $facetResults["cost"]->getValues()[0]->getCount());
                $this->assertEquals("cost >= 300 and cost <= 400", $facetResults["cost"]->getValues()[1]->getRange());
                $this->assertEquals(2, $facetResults["cost"]->getValues()[1]->getCount());
                $this->assertEquals("cost >= 500 and cost <= 600", $facetResults["cost"]->getValues()[2]->getRange());
                $this->assertEquals(2, $facetResults["cost"]->getValues()[2]->getCount());
                $this->assertEquals("cost >= 700 and cost <= 800", $facetResults["cost"]->getValues()[3]->getRange());
                $this->assertEquals(2, $facetResults["cost"]->getValues()[3]->getCount());
                $this->assertEquals("cost >= 900", $facetResults["cost"]->getValues()[4]->getRange());
                $this->assertEquals(1, $facetResults["cost"]->getValues()[4]->getCount());

                $this->assertCount(4, $facetResults["megapixels"]->getValues());
                $this->assertEquals("megapixels <= 3", $facetResults["megapixels"]->getValues()[0]->getRange());
                $this->assertEquals(4, $facetResults["megapixels"]->getValues()[0]->getCount());
                $this->assertEquals("megapixels >= 4 and megapixels <= 7", $facetResults["megapixels"]->getValues()[1]->getRange());
                $this->assertEquals(4, $facetResults["megapixels"]->getValues()[1]->getCount());
                $this->assertEquals("megapixels >= 8 and megapixels <= 10", $facetResults["megapixels"]->getValues()[2]->getRange());
                $this->assertEquals(2, $facetResults["megapixels"]->getValues()[2]->getCount());
                $this->assertEquals("megapixels >= 11", $facetResults["megapixels"]->getValues()[3]->getRange());
                $this->assertEquals(0, $facetResults["megapixels"]->getValues()[3]->getCount());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $r1 = $session
                        ->advanced()
                        ->rawQuery(Camera::class, "from index 'CameraCost' where cost < 200 select facet(id('facets/CameraFacets'))")
                        ->executeAggregation();

                $r2 = $session
                        ->advanced()
                        ->rawQuery(Camera::class, "from index 'CameraCost' where megapixels < 3 select facet(id('facets/CameraFacets'))")
                        ->executeAggregation();

                $multiFacetResults = [$r1, $r2];

                $this->assertCount(3, $multiFacetResults[0]);

                $this->assertCount(2, $multiFacetResults[0]["manufacturer"]->getValues());
                $this->assertEquals("manufacturer1", $multiFacetResults[0]["manufacturer"]->getValues()[0]->getRange());
                $this->assertEquals(1, $multiFacetResults[0]["manufacturer"]->getValues()[0]->getCount());
                $this->assertEquals("manufacturer2", $multiFacetResults[0]["manufacturer"]->getValues()[1]->getRange());
                $this->assertEquals(1, $multiFacetResults[0]["manufacturer"]->getValues()[1]->getCount());

                $this->assertCount(5, $multiFacetResults[0]["cost"]->getValues());

                $this->assertEquals("cost <= 200", $multiFacetResults[0]["cost"]->getValues()[0]->getRange());
                $this->assertEquals(2, $multiFacetResults[0]["cost"]->getValues()[0]->getCount());
                $this->assertEquals("cost >= 300 and cost <= 400", $multiFacetResults[0]["cost"]->getValues()[1]->getRange());
                $this->assertEquals(0, $multiFacetResults[0]["cost"]->getValues()[1]->getCount());
                $this->assertEquals("cost >= 500 and cost <= 600", $multiFacetResults[0]["cost"]->getValues()[2]->getRange());
                $this->assertEquals(0, $multiFacetResults[0]["cost"]->getValues()[2]->getCount());
                $this->assertEquals("cost >= 700 and cost <= 800", $multiFacetResults[0]["cost"]->getValues()[3]->getRange());
                $this->assertEquals(0, $multiFacetResults[0]["cost"]->getValues()[3]->getCount());
                $this->assertEquals("cost >= 900", $multiFacetResults[0]["cost"]->getValues()[4]->getRange());
                $this->assertEquals(0, $multiFacetResults[0]["cost"]->getValues()[4]->getCount());

                $this->assertCount(4, $multiFacetResults[0]["megapixels"]->getValues());
                $this->assertEquals("megapixels <= 3", $multiFacetResults[0]["megapixels"]->getValues()[0]->getRange());
                $this->assertEquals(2, $multiFacetResults[0]["megapixels"]->getValues()[0]->getCount());
                $this->assertEquals("megapixels >= 4 and megapixels <= 7", $multiFacetResults[0]["megapixels"]->getValues()[1]->getRange());
                $this->assertEquals(0, $multiFacetResults[0]["megapixels"]->getValues()[1]->getCount());
                $this->assertEquals("megapixels >= 8 and megapixels <= 10", $multiFacetResults[0]["megapixels"]->getValues()[2]->getRange());
                $this->assertEquals(0, $multiFacetResults[0]["megapixels"]->getValues()[2]->getCount());
                $this->assertEquals("megapixels >= 11", $multiFacetResults[0]["megapixels"]->getValues()[3]->getRange());
                $this->assertEquals(0, $multiFacetResults[0]["megapixels"]->getValues()[3]->getCount());


                $this->assertCount(3, $multiFacetResults[1]);

                $this->assertCount(2, $multiFacetResults[1]["manufacturer"]->getValues());
                $this->assertEquals("manufacturer1", $multiFacetResults[1]["manufacturer"]->getValues()[0]->getRange());
                $this->assertEquals(2, $multiFacetResults[1]["manufacturer"]->getValues()[0]->getCount());
                $this->assertEquals("manufacturer2", $multiFacetResults[1]["manufacturer"]->getValues()[1]->getRange());
                $this->assertEquals(1, $multiFacetResults[1]["manufacturer"]->getValues()[1]->getCount());

                $this->assertCount(5, $multiFacetResults[1]["cost"]->getValues());

                $this->assertEquals("cost <= 200", $multiFacetResults[1]["cost"]->getValues()[0]->getRange());
                $this->assertEquals(3, $multiFacetResults[1]["cost"]->getValues()[0]->getCount());
                $this->assertEquals("cost >= 300 and cost <= 400", $multiFacetResults[1]["cost"]->getValues()[1]->getRange());
                $this->assertEquals(0, $multiFacetResults[1]["cost"]->getValues()[1]->getCount());
                $this->assertEquals("cost >= 500 and cost <= 600", $multiFacetResults[1]["cost"]->getValues()[2]->getRange());
                $this->assertEquals(0, $multiFacetResults[1]["cost"]->getValues()[2]->getCount());
                $this->assertEquals("cost >= 700 and cost <= 800", $multiFacetResults[1]["cost"]->getValues()[3]->getRange());
                $this->assertEquals(0, $multiFacetResults[1]["cost"]->getValues()[3]->getCount());
                $this->assertEquals("cost >= 900", $multiFacetResults[1]["cost"]->getValues()[4]->getRange());
                $this->assertEquals(0, $multiFacetResults[1]["cost"]->getValues()[4]->getCount());

                $this->assertCount(4, $multiFacetResults[1]["megapixels"]->getValues());
                $this->assertEquals("megapixels <= 3", $multiFacetResults[1]["megapixels"]->getValues()[0]->getRange());
                $this->assertEquals(3, $multiFacetResults[1]["megapixels"]->getValues()[0]->getCount());
                $this->assertEquals("megapixels >= 4 and megapixels <= 7", $multiFacetResults[1]["megapixels"]->getValues()[1]->getRange());
                $this->assertEquals(0, $multiFacetResults[1]["megapixels"]->getValues()[1]->getCount());
                $this->assertEquals("megapixels >= 8 and megapixels <= 10", $multiFacetResults[1]["megapixels"]->getValues()[2]->getRange());
                $this->assertEquals(0, $multiFacetResults[1]["megapixels"]->getValues()[2]->getCount());
                $this->assertEquals("megapixels >= 11", $multiFacetResults[1]["megapixels"]->getValues()[3]->getRange());
                $this->assertEquals(0, $multiFacetResults[1]["megapixels"]->getValues()[3]->getCount());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testUsingToListOnRawFacetQueryShouldThrow(): void
    {
        $store = $this->getDocumentStore();
        try {
            $index = new CameraCostIndex();
            $index->execute($store);

            $facets = [];
            $facet1 = new Facet();
            $facet1->setFieldName("manufacturer");

            $facets[] = $facet1;

            $rangeFacets = [];
            $rangeFacet1 = new RangeFacet();
            $rangeFacet1->setRanges([
                "cost <= 200",
                "cost >= 300 and cost <= 400",
                "cost >= 500 and cost <= 600",
                "cost >= 700 and cost <= 800",
                "cost >= 900"
            ]);

            $rangeFacet2 = new RangeFacet();
            $rangeFacet2->setRanges([
                "megapixels <= 3",
                "megapixels >= 4 and megapixels <= 7",
                "megapixels >= 8 and megapixels <= 10",
                "megapixels >= 11"
            ]);

            $rangeFacets[] = $rangeFacet1;
            $rangeFacets[] = $rangeFacet2;

            $session = $store->openSession();
            try {
                $facetSetup = new FacetSetup();
                $facetSetup->setId("facets/CameraFacets");
                $facetSetup->setFacets($facets);
                $facetSetup->setRangeFacets($rangeFacets);
                $session->store($facetSetup);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                try {
                    $session->advanced()->rawQuery(Camera::class, "from index 'CameraCost' select facet(id('facets/CameraFacets'))")
                        ->toList();

                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertStringStartsWith("Raw query with aggregation by facet should be called by executeAggregation method", $exception->getMessage());
                }

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
