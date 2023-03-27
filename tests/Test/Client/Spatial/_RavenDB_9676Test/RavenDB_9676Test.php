<?php

namespace tests\RavenDB\Test\Client\Spatial\_RavenDB_9676Test;

use RavenDB\Documents\Queries\Spatial\PointField;
use tests\RavenDB\RemoteTestBase;

class RavenDB_9676Test extends RemoteTestBase
{
    public function testCanOrderByDistanceOnDynamicSpatialField(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $item = new Item();
                $item->setName("Item1");
                $item->setLatitude(10);
                $item->setLongitude(10);

                $session->store($item);

                $item1 = new Item();
                $item1->setName("Item2");
                $item1->setLatitude(11);
                $item1->setLongitude(11);

                $session->store($item1);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $items = $session->query(Item::class)
                        ->waitForNonStaleResults()
                        ->spatial(new PointField("latitude", "longitude"), function($f) { return $f->withinRadius(1000, 10, 10); })
                        ->orderByDistance(new PointField("latitude", "longitude"), 10, 10)
                        ->toList();

                $this->assertCount(2, $items);

                $this->assertEquals("Item1", $items[0]->getName());
                $this->assertEquals("Item2", $items[1]->getName());

                $items = $session->query(Item::class)
                        ->waitForNonStaleResults()
                        ->spatial(new PointField("latitude", "longitude"), function($f) { return $f->withinRadius(1000, 10, 10); })
                        ->orderByDistanceDescending(new PointField("latitude", "longitude"), 10, 10)
                        ->toList();

                $this->assertCount(2, $items);

                $this->assertEquals("Item2", $items[0]->getName());
                $this->assertEquals("Item1", $items[1]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
