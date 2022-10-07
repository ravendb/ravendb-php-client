<?php

namespace tests\RavenDB\Test\Faceted;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;
use RavenDB\Documents\Queries\Facets\Facet;
use RavenDB\Documents\Queries\Facets\FacetOptions;
use RavenDB\Documents\Queries\Facets\FacetResult;
use RavenDB\Documents\Queries\Facets\FacetSetup;
use RavenDB\Documents\Queries\Facets\FacetTermSortMode;
use RavenDB\Documents\Queries\Facets\FacetValue;
use RavenDB\Documents\Queries\Query;
use tests\RavenDB\Test\_FacetTestBase\Camera;
use tests\RavenDB\Test\_FacetTestBase\CameraList;
use tests\RavenDB\Test\_FacetTestBase\FacetTestBase;

class FacetPagingTest extends FacetTestBase
{
    private CameraList $data;
    private static int $numCameras = 1000;

    public function __construct()
    {
        parent::__construct();
        $this->data = $this->getCameras(self::$numCameras);
    }

    public function testCanPerformFacetedPagingSearchWithNoPageSizeNoMaxResults_HitsDesc(): void
    {
        $facetOptions = new FacetOptions();
        $facetOptions->setStart(2);
        $facetOptions->setTermSortMode(FacetTermSortMode::countDesc());
        $facetOptions->setIncludeRemainingTerms(true);

        $facet = new Facet();
        $facet->setFieldName("manufacturer");
        $facet->setOptions($facetOptions);

        $facets = [$facet];

        $store = $this->getDocumentStore();
        try {
            $this->setupStore($store);

            $session = $store->openSession();
            try {
                $facetSetup = new FacetSetup();
                $facetSetup->setId("facets/CameraFacets");
                $facetSetup->setFacets($facets);
                $session->store($facetSetup);
                $session->saveChanges();

                /** @var array<FacetResult> $facetResults */
                $facetResults = $session->query(Camera::class, Query::index("CameraCost"))
                    ->aggregateUsing("facets/CameraFacets")
                    ->execute();

                $cameraCounts = [];
                /** @var Camera $camera */
                foreach ($this->data as $camera) {
                    $cameraCounts[$camera->getManufacturer()] =
                        array_key_exists($camera->getManufacturer(), $cameraCounts) ?
                            $cameraCounts[$camera->getManufacturer()] + 1 : 1;
                }

                array_multisort(array_values($cameraCounts), SORT_DESC, array_keys($cameraCounts), SORT_ASC, $cameraCounts);
                $cameraCounts = array_change_key_case($cameraCounts, CASE_LOWER);
                $camerasByHits = array_keys(array_slice($cameraCounts, 2));

                $this->assertCount(3, $facetResults["manufacturer"]->getValues());

                $this->assertEquals($camerasByHits[0], $facetResults["manufacturer"]->getValues()[0]->getRange());
                $this->assertEquals($camerasByHits[1], $facetResults["manufacturer"]->getValues()[1]->getRange());
                $this->assertEquals($camerasByHits[2], $facetResults["manufacturer"]->getValues()[2]->getRange());

                /** @var FacetValue $f */
                foreach ($facetResults["manufacturer"]->getValues() as $f) {
                    $inMemoryCount = $cameraCounts[$f->getRange()];
                    $this->assertEquals($inMemoryCount, $f->getCount());
                }

                $this->assertEquals(0, $facetResults["manufacturer"]->getRemainingTermsCount());
                $this->assertCount(0, $facetResults["manufacturer"]->getRemainingTerms());
                $this->assertEquals(0, $facetResults["manufacturer"]->getRemainingHits());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanPerformFacetedPagingSearchWithNoPageSizeWithMaxResults_HitsDesc(): void
    {
        $facetOptions = new FacetOptions();
        $facetOptions->setStart(2);
        $facetOptions->setPageSize(2);
        $facetOptions->setTermSortMode(FacetTermSortMode::countDesc());
        $facetOptions->setIncludeRemainingTerms(true);

        $facet = new Facet();
        $facet->setFieldName("manufacturer");
        $facet->setOptions($facetOptions);

        $facets = [$facet];

        $store = $this->getDocumentStore();
        try {
            $this->setupStore($store);

            $session = $store->openSession();
            try {
                $facetSetup = new FacetSetup();
                $facetSetup->setId("facets/CameraFacets");
                $facetSetup->setFacets($facets);
                $session->store($facetSetup);
                $session->saveChanges();

                /** @var array<FacetResult> $facetResults */
                $facetResults = $session->query(Camera::class, Query::index("CameraCost"))
                        ->aggregateUsing("facets/CameraFacets")
                        ->execute();

                $cameraCounts = [];
                /** @var Camera $camera */
                foreach ($this->data as $camera) {
                    $cameraCounts[$camera->getManufacturer()] =
                        array_key_exists($camera->getManufacturer(), $cameraCounts) ?
                            $cameraCounts[$camera->getManufacturer()] + 1 : 1;
                }

                array_multisort(array_values($cameraCounts), SORT_DESC, array_keys($cameraCounts), SORT_ASC, $cameraCounts);
                $cameraCounts = array_change_key_case($cameraCounts, CASE_LOWER);
                $camerasByHits = array_keys(array_slice($cameraCounts, 2));

                $indexOfSpliced = $camerasByHits[count($camerasByHits) - 1];
                array_splice($camerasByHits, 2);

                $this->assertCount(2, $facetResults["manufacturer"]->getValues());

                $this->assertEquals($camerasByHits[0], $facetResults["manufacturer"]->getValues()[0]->getRange());
                $this->assertEquals($camerasByHits[1], $facetResults["manufacturer"]->getValues()[1]->getRange());

                /** @var FacetValue $f */
                foreach ($facetResults["manufacturer"]->getValues() as $f) {
                    $inMemoryCount = $cameraCounts[$f->getRange()];
                    $this->assertEquals($inMemoryCount, $f->getCount());
                }

                $this->assertEquals(1, $facetResults["manufacturer"]->getRemainingTermsCount());
                $this->assertCount(1, $facetResults["manufacturer"]->getRemainingTerms());
                $this->assertEquals($cameraCounts[$indexOfSpliced], $facetResults["manufacturer"]->getRemainingHits());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    private function setupStore(DocumentStoreInterface $store): void
    {
        $s = $store->openSession();
        try {

            $indexDefinition = new IndexDefinition();
            $indexDefinition->setName("CameraCost");
            $indexDefinition->setMaps(["from camera in docs select new { camera.manufacturer, camera.model, camera.cost, camera.dateOfListing, camera.megapixels } "]);

            $store->maintenance()->send(new PutIndexesOperation($indexDefinition));

            $counter = 0;
            /** @var Camera $camera */
            foreach ($this->data as $camera) {
                $s->store($camera);
                $counter++;

                if ($counter % (self::$numCameras / 25) == 0) {
                    $s->saveChanges();
                }
            }

            $s->saveChanges();
        } finally {
            $s->close();
        }

        $this->waitForIndexing($store);
    }
}
