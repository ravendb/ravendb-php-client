<?php

namespace tests\RavenDB\Test\Issues\RavenDB_13682Test;

use RavenDB\Documents\Queries\Query;
use RavenDB\Documents\Session\MetadataDictionaryInterface;
use RavenDB\Documents\Smuggler\DatabaseItemType;
use RavenDB\Documents\Smuggler\DatabaseItemTypeSet;
use tests\RavenDB\Infrastructure\CreateSampleDataOperation;
use tests\RavenDB\Infrastructure\Entity\Order;
use tests\RavenDB\RemoteTestBase;

class RavenDB_13682Test extends RemoteTestBase
{
    // @todo: uncomment this test
    // !spatials
    public function atestCanQueryByRoundedSpatialRanges(): void
    {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession s = store.openSession()) {
//                // 35.1, -106.3 - destination
//                Item item1 = new Item();  // 3rd dist - 72.7 km
//                item1.setLat(35.1);
//                item1.setLng(-107.1);
//                item1.setName("a");
//                s.store(item1);
//
//                Item item2 = new Item(); // 2nd dist - 64.04 km
//                item2.setLat(35.2);
//                item2.setLng(-107.0);
//                item2.setName("b");
//                s.store(item2);
//
//                Item item3 = new Item(); // 1st dist - 28.71 km
//                item3.setLat(35.3);
//                item3.setLng(-106.5);
//                item3.setName("c");
//                s.store(item3);
//
//                s.saveChanges();
//            }
//
//            try (IDocumentSession s = store.openSession()) {
//                // we sort first by spatial distance (but round it up to 25km)
//                // then we sort by name ascending, so within 25 range, we can apply a different sort
//
//                List<Item> result = s.advanced().rawQuery(Item.class, "from Items as a " +
//                        "order by spatial.distance(spatial.point(a.lat, a.lng), spatial.point(35.1, -106.3), 25), name")
//                        .toList();
//
//                assertThat(result)
//                        .hasSize(3);
//
//                assertThat(result.get(0).getName())
//                        .isEqualTo("c");
//                assertThat(result.get(1).getName())
//                        .isEqualTo("a");
//                assertThat(result.get(2).getName())
//                        .isEqualTo("b");
//            }
//
//            // dynamic query
//            try (IDocumentSession s = store.openSession()) {
//                // we sort first by spatial distance (but round it up to 25km)
//                // then we sort by name ascending, so within 25 range, we can apply a different sort
//
//                IDocumentQuery<Item> query = s.query(Item.class)
//                        .orderByDistance(new PointField("lat", "lng").roundTo(25), 35.1, -106.3);
//                List<Item> result = query.toList();
//
//                assertThat(result)
//                        .hasSize(3);
//
//                assertThat(result.get(0).getName())
//                        .isEqualTo("c");
//                assertThat(result.get(1).getName())
//                        .isEqualTo("a");
//                assertThat(result.get(2).getName())
//                        .isEqualTo("b");
//            }
//
//            new SpatialIndex().execute(store);
//            waitForIndexing(store);
//
//            try (IDocumentSession s = store.openSession()) {
//                // we sort first by spatial distance (but round it up to 25km)
//                // then we sort by name ascending, so within 25 range, we can apply a different sort
//
//                IDocumentQuery<Item> query = s.query(Item.class, SpatialIndex.class)
//                        .orderByDistance("coordinates", 35.1, -106.3, 25);
//
//                List<Item> result = query.toList();
//
//                assertThat(result)
//                        .hasSize(3);
//
//                assertThat(result.get(0).getName())
//                        .isEqualTo("c");
//                assertThat(result.get(1).getName())
//                        .isEqualTo("a");
//                assertThat(result.get(2).getName())
//                        .isEqualTo("b");
//            }
//        }
    }

    public function testCanUseDynamicQueryOrderBySpatial_WithAlias(): void
    {
        $store = $this->getDocumentStore();
        try {

            $set = new DatabaseItemTypeSet();
            $set->append(DatabaseItemType::documents());
            $set->append(DatabaseItemType::indexes());
            $store->maintenance()->send(new CreateSampleDataOperation($set));

            $session = $store->openSession();
            try {
                $d = $session->advanced()->rawQuery(Order::class, "from Orders  as a\n" .
                        "order by spatial.distance(\n" .
                        "    spatial.point(a.ShipTo.Location.Latitude, a.ShipTo.Location.Longitude),\n" .
                        "    spatial.point(35.2, -107.2 )\n" .
                        ")\n" .
                        "limit 1")
                    ->single()
                ;

                $metadata = $session->advanced()->getMetadataFor($d);

                $spatial = $metadata->get("@spatial");
                $this->assertEqualsWithDelta(48.99, $spatial['Distance'], 0.01);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetDistanceFromSpatialQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $set = new DatabaseItemTypeSet();
            $set->append(DatabaseItemType::documents());
            $set->append(DatabaseItemType::indexes());
            $store->maintenance()->send(new CreateSampleDataOperation($set));

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $d = $session->query(Order::class, Query::index("Orders/ByShipment/Location"))
                        ->whereEquals("id()", "orders/830-A")
                        ->orderByDistance("ShipmentLocation", 35.2, -107.1)
                        ->single();

                $metadata = $session->advanced()->getMetadataFor($d);

                $spatial = $metadata->get("@spatial");
                $this->assertEqualsWithDelta(40.1, $spatial['Distance'], 0.1);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
