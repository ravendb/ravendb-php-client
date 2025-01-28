<?php

namespace tests\RavenDB\Test\Issues\RavenDB_8328Test;

use RavenDB\Documents\Queries\Spatial\PointField;
use RavenDB\Documents\Queries\Spatial\WktField;
use RavenDB\Documents\Session\QueryStatistics;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;

class RavenDB_8328Test extends RemoteTestBase
{
    public function testSpatialOnAutoIndex(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $item = new Item();
                $item->setLatitude(10);
                $item->setLongitude(20);
                $item->setLatitude2(10);
                $item->setLongitude2(20);
                $item->setShapeWkt("POINT(20 10)");
                $item->setName("Name1");

                $session->store($item);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $q = $session->query(Item::class)
                        ->spatial(new PointField("latitude", "longitude"), function($f) { return $f->withinRadius(10, 10, 20); });

                $iq = $q->getIndexQuery();
                $this->assertEquals("from 'Items' where spatial.within(spatial.point(latitude, longitude), spatial.circle(\$p0, \$p1, \$p2))", $iq->getQuery());

                $q = $session->query(Item::class)
                        ->spatial(new WktField("shapeWkt"), function($f) { return $f->withinRadius(10, 10, 20); });

                $iq = $q->getIndexQuery();
                $this->assertEquals("from 'Items' where spatial.within(spatial.wkt(shapeWkt), spatial.circle(\$p0, \$p1, \$p2))", $iq->getQuery());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $statsRef = new QueryStatistics();
                $results = $session->query(Item::class)
                        ->statistics($statsRef)
                        ->spatial(new PointField("latitude", "longitude"), function($f) { return $f->withinRadius(10, 10, 20); })
                        ->toList();

                $this->assertCount(1, $results);

                $this->assertEquals("Auto/Items/BySpatial.point(latitude|longitude)", $statsRef->getIndexName());

                $statsRef = new QueryStatistics();
                $results = $session->query(Item::class)
                        ->statistics($statsRef)
                        ->spatial(new PointField("latitude2", "longitude2"), function($f) { return $f->withinRadius(10, 10, 20); })
                        ->toList();

                $this->assertCount(1, $results);

                $this->assertEquals("Auto/Items/BySpatial.point(latitude|longitude)AndSpatial.point(latitude2|longitude2)", $statsRef->getIndexName());

                $statsRef = new QueryStatistics();
                $results = $session->query(Item::class)
                        ->statistics($statsRef)
                        ->spatial(new WktField("shapeWkt"), function($f) { return $f->withinRadius(10, 10, 20); })
                        ->toList();

                $this->assertCount(1, $results);

                $this->assertEquals("Auto/Items/BySpatial.point(latitude|longitude)AndSpatial.point(latitude2|longitude2)AndSpatial.wkt(shapeWkt)", $statsRef->getIndexName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
