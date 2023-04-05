<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialQueriesTest;

use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Indexes\Spatial\SpatialUnits;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;
use RavenDB\Documents\Queries\Query;
use tests\RavenDB\RemoteTestBase;

class SpatialQueriesTest extends RemoteTestBase
{
    /** @doesNotPerformAssertions */
    public function testCanRunSpatialQueriesInMemory(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new SpatialQueriesInMemoryTestIdx())->execute($store);
        } finally {
            $store->close();
        }
    }

    public function testCanSuccessfullyDoSpatialQueryOfNearbyLocations(): void
    {
        // These items is in a radius of 4 miles (approx 6,5 km)
        $areaOneDocOne = new DummyGeoDoc(55.6880508001, 13.5717346673);
        $areaOneDocTwo = new DummyGeoDoc(55.6821978456, 13.6076183965);
        $areaOneDocThree = new DummyGeoDoc(55.673251569, 13.5946697607);

        // This item is 12 miles (approx 19 km) from the closest in areaOne
        $closeButOutsideAreaOne = new DummyGeoDoc(55.8634157297, 13.5497731987);

        // This item is about 3900 miles from areaOne
        $newYork = new DummyGeoDoc(40.7137578228, -74.0126901936);

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $session->store($areaOneDocOne);
                $session->store($areaOneDocTwo);
                $session->store($areaOneDocThree);
                $session->store($closeButOutsideAreaOne);
                $session->store($newYork);
                $session->saveChanges();

                $indexDefinition = new IndexDefinition();
                $indexDefinition->setName("FindByLatLng");
                $indexDefinition->setMaps(["from doc in docs select new { coordinates = CreateSpatialField(doc.latitude, doc.longitude) }"]);

                $store->maintenance()->send(new PutIndexesOperation($indexDefinition));

                // Wait until the index is built
                $session->query(DummyGeoDoc::class, Query::index("FindByLatLng"))
                        ->waitForNonStaleResults()
                        ->toList();

                $lat = 55.6836422426;
                $lng = 13.5871808352; // in the middle of AreaOne
                $radius = 5.0;

                $nearbyDocs = $session->query(DummyGeoDoc::class, Query::index("FindByLatLng"))
                        ->withinRadiusOf("coordinates", $radius, $lat, $lng)
                        ->waitForNonStaleResults()
                        ->toList();

                $this->assertNotNull($nearbyDocs);
                $this->assertCount(3, $nearbyDocs);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanSuccessfullyQueryByMiles(): void
    {
        $myHouse = new DummyGeoDoc(44.757767, -93.355322);

        // The gym is about 7.32 miles (11.79 kilometers) from my house.
        $gym = new DummyGeoDoc(44.682861, -93.25);

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $session->store($myHouse);
                $session->store($gym);
                $session->saveChanges();

                $indexDefinition = new IndexDefinition();
                $indexDefinition->setName("FindByLatLng");
                $indexDefinition->setMaps(["from doc in docs select new { coordinates = CreateSpatialField(doc.latitude, doc.longitude) }"]);

                $store->maintenance()->send(new PutIndexesOperation($indexDefinition));

                // Wait until the index is built
                $session->query(DummyGeoDoc::class, Query::index("FindByLatLng"))
                        ->waitForNonStaleResults()
                        ->toList();

                $radius = 8.0;

                // Find within 8 miles.
                // We should find both my house and the gym.
                $matchesWithinMiles = $session->query(DummyGeoDoc::class, Query::index("FindByLatLng"))
                        ->withinRadiusOf("coordinates", $radius, $myHouse->getLatitude(), $myHouse->getLongitude(), SpatialUnits::miles())
                        ->waitForNonStaleResults()
                        ->toList();

                $this->assertNotNull($matchesWithinMiles);
                $this->assertCount(2, $matchesWithinMiles);

                // Find within 8 kilometers.
                // We should find only my house, since the gym is ~11 kilometers out.

                $matchesWithinKilometers = $session->query(DummyGeoDoc::class, Query::index("FindByLatLng"))
                        ->withinRadiusOf("coordinates", $radius, $myHouse->getLatitude(), $myHouse->getLongitude(), SpatialUnits::kilometers())
                        ->waitForNonStaleResults()
                        ->toList();

                $this->assertNotNull($matchesWithinKilometers);
                $this->assertCount(1, $matchesWithinKilometers);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
