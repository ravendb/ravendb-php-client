<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialTest;

use DateTime;
use RavenDB\Documents\Session\QueryStatistics;
use tests\RavenDB\RemoteTestBase;

class SpatialTest extends RemoteTestBase
{
    public function testWeirdSpatialResults(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $myDocument = new MyDocument();
                $myDocument->setId("First");

                $myDocumentItem = new MyDocumentItem();
                $myDocumentItem->setDate(new DateTime());
                $myDocumentItem->setLatitude(10.0);
                $myDocumentItem->setLongitude(10.0);

                $myDocument->setItems([ $myDocumentItem ]);

                $session->store($myDocument);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            (new MyIndex())->execute($store);

            $session = $store->openSession();
            try {
                $statsRef = new QueryStatistics();

                $result = $session->advanced()->documentQuery(MyDocument::class, MyIndex::class)
                        ->waitForNonStaleResults()
                        ->withinRadiusOf("coordinates", 0, 12.3456789, 12.3456789)
                        ->statistics($statsRef)
                        ->selectFields(MyProjection::class, "id", "latitude", "longitude")
                        ->take(50)
                        ->toList();

                $this->assertEquals(0, $statsRef->getTotalResults());

                $this->assertCount(0, $result);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testMatchSpatialResults(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $myDocument = new MyDocument();
                $myDocument->setId("First");

                $myDocumentItem = new MyDocumentItem();
                $myDocumentItem->setDate(new DateTime());
                $myDocumentItem->setLatitude(10.0);
                $myDocumentItem->setLongitude(10.0);

                $myDocument->setItems([ $myDocumentItem ]);

                $session->store($myDocument);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            (new MyIndex())->execute($store);

            $session = $store->openSession();
            try {
                $statsRef = new QueryStatistics();

                $result = $session->advanced()->documentQuery(MyDocument::class, MyIndex::class)
                        ->waitForNonStaleResults()
                        ->withinRadiusOf("coordinates", 1, 10, 10)
                        ->statistics($statsRef)
                        ->selectFields(MyProjection::class, "id", "latitude", "longitude")
                        ->take(50)
                        ->toList();

                $this->assertEquals(1, $statsRef->getTotalResults());

                $this->assertCount(1, $result);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
