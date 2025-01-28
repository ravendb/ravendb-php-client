<?php

namespace tests\RavenDB\Test\Client\Spatial\_BoundingBoxIndexTest;

use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;

class BoundingBoxIndexTest extends RemoteTestBase
{
    public function testBoundingBoxTest(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $polygon = "POLYGON ((0 0, 0 5, 1 5, 1 1, 5 1, 5 5, 6 5, 6 0, 0 0))";
        $rectangle1 = "2 2 4 4";
        $rectangle2 = "6 6 10 10";
        $rectangle3 = "0 0 6 6";

        $store = $this->getDocumentStore();
        try {
            (new BBoxIndex())->execute($store);
            (new QuadTreeIndex())->execute($store);

            $session = $store->openSession();
            try {
                $doc = new SpatialDoc();
                $doc->setShape($polygon);
                $session->store($doc);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $result = $session->query(SpatialDoc::class)
                        ->count();
                $this->assertEquals(1, $result);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $result = $session->query(SpatialDoc::class, BBoxIndex::class)
                        ->spatial("shape", function($x) use($rectangle1) { return $x->intersects($rectangle1); })
                        ->count();

                $this->assertEquals(1, $result);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $result = $session->query(SpatialDoc::class, BBoxIndex::class)
                        ->spatial("shape", function($x) use ($rectangle2) { return $x->intersects($rectangle2); })
                        ->count();

                $this->assertEquals(0, $result);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $result = $session->query(SpatialDoc::class, BBoxIndex::class)
                        ->spatial("shape", function($x) use ($rectangle2) { return $x->disjoint($rectangle2); })
                         ->count();

                $this->assertEquals(1, $result);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $result = $session->query(SpatialDoc::class, BBoxIndex::class)
                        ->spatial("shape", function($x) use ($rectangle3) { return $x->within($rectangle3); })
                        ->count();

                $this->assertEquals(1, $result);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $result = $session->query(SpatialDoc::class, QuadTreeIndex::class)
                        ->spatial("shape", function($x) use ($rectangle2) { return $x->intersects($rectangle2); })
                        ->count();

                $this->assertEquals(0, $result);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $result = $session->query(SpatialDoc::class, QuadTreeIndex::class)
                        ->spatial("shape", function($x) use ($rectangle1) { return $x->intersects($rectangle1); })
                        ->count();

                $this->assertEquals(0, $result);
            } finally {
                $session->close();
            }

        } finally {
            $store->close();
        }
    }
}
