<?php

namespace tests\RavenDB\Test\Client\Spatial\_SimonBartlettTest;

use RavenDB\Documents\Indexes\Spatial\SpatialRelation;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;

class SimonBartlettTest extends RemoteTestBase
{
    public function testLineStringsShouldIntersect(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new GeoIndex());

            $session = $store->openSession();
            try {
                $geoDocument = new GeoDocument();
                $geoDocument->setWkt("LINESTRING (0 0, 1 1, 2 1)");
                $session->store($geoDocument);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $count = $session->query(null, GeoIndex::class)
                        ->spatial("WKT", function($f) { return $f->relatesToShape("LINESTRING (1 0, 1 1, 1 2)", SpatialRelation::intersects()); })
                        ->waitForNonStaleResults()
                        ->count();

                $this->assertEquals(1, $count);

                $count = $session->query(null, GeoIndex::class)
                        ->relatesToShape("WKT", "LINESTRING (1 0, 1 1, 1 2)", SpatialRelation::intersects())
                        ->waitForNonStaleResults()
                        ->count();

                $this->assertEquals(1, $count);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCirclesShouldNotIntersect(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new GeoIndex());

            $session = $store->openSession();
            try {
                // 110km is approximately 1 degree
                $geoDocument = new GeoDocument();
                $geoDocument->setWkt("CIRCLE(0.000000 0.000000 d=110)");
                $session->store($geoDocument);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                // Should not intersect, as there is 1 Degree between the two shapes
                $count = $session->query(null, GeoIndex::class)
                        ->spatial("WKT", function($f) { return $f->relatesToShape("CIRCLE(0.000000 3.000000 d=110)", SpatialRelation::intersects()); })
                        ->waitForNonStaleResults()
                        ->count();

                $this->assertEquals(0, $count);

                $count = $session->query(null, GeoIndex::class)
                        ->relatesToShape("WKT", "CIRCLE(0.000000 3.000000 d=110)", SpatialRelation::intersects())
                        ->waitForNonStaleResults()
                        ->count();

                $this->assertEquals(0, $count);
            } finally {
                $session->close();
            }
        } finally {
           $store->close();
        }
    }

}
