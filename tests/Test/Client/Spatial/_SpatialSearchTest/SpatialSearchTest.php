<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialSearchTest;

use DateTime;
use RavenDB\Documents\Queries\Query;
use RavenDB\Documents\Session\QueryStatistics;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;

class SpatialSearchTest extends RemoteTestBase
{
    public function test_can_do_spatial_search_with_client_api(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new SpatialIdx())->execute($store);

            $session = $store->openSession();
            try {
                $session->store(new Event("a/1", 38.9579000, -77.3572000, new DateTime()));
                $session->store(new Event("a/2", 38.9690000, -77.3862000, DateUtils::addDays(new DateTime(), 1)));
                $session->store(new Event("b/2", 38.9690000, -77.3862000, DateUtils::addDays(new DateTime(), 2)));
                $session->store(new Event("c/3", 38.9510000, -77.4107000, DateUtils::addYears(new DateTime(), 3)));
                $session->store(new Event("d/1", 37.9510000, -77.4107000, DateUtils::addYears(new DateTime(), 3)));
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $statsRef = new QueryStatistics();
                $events = $session->query(Event::class, Query::index("SpatialIdx"))
                        ->statistics($statsRef)
                        ->whereLessThanOrEqual("date", DateUtils::addYears(new DateTime(), 1))
                        ->withinRadiusOf("coordinates", 6.0, 38.96939, -77.386398)
                        ->orderByDescending("date")
                        ->toList();

                $this->assertNotEmpty($events);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_can_do_spatial_search_with_client_api3(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new SpatialIdx())->execute($store);

            $session = $store->openSession();
            try {
                $matchingVenues = $session->advanced()->documentQuery(Event::class, SpatialIdx::class)
                        ->spatial("coordinates", function($f) { return $f->withinRadius(5, 38.9103000, -77.3942); })
                        ->waitForNonStaleResults();

                $iq = $matchingVenues->getIndexQuery();

                $this->assertEquals("from index 'SpatialIdx' where spatial.within(coordinates, spatial.circle(\$p0, \$p1, \$p2))", $iq->getQuery());

                $this->assertEquals(5.0, $iq->getQueryParameters()["p0"]);
                $this->assertEquals(38.9103, $iq->getQueryParameters()["p1"]);
                $this->assertEquals(-77.3942, $iq->getQueryParameters()["p2"]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_can_do_spatial_search_with_client_api_within_given_capacity(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            (new SpatialIdx())->execute($store);

            $session = $store->openSession();
            try {
                $session->store(new Event("a/1", 38.9579000, -77.3572000, new DateTime(), 5000));
                $session->store(new Event("a/2", 38.9690000, -77.3862000, DateUtils::addDays(new DateTime(), 1), 5000));
                $session->store(new Event("b/2", 38.9690000, -77.3862000, DateUtils::addDays(new DateTime(), 2), 2000));
                $session->store(new Event("c/3", 38.9510000, -77.4107000, DateUtils::addYears(new DateTime(), 3), 1500));
                $session->store(new Event("d/1", 37.9510000, -77.4107000, DateUtils::addYears(new DateTime(), 3), 1500));
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $queryStats = new QueryStatistics();

                $events = $session->query(Event::class, Query::index("SpatialIdx"))
                        ->statistics($queryStats)
                        ->openSubclause()
                            ->whereGreaterThanOrEqual("capacity", 0)
                            ->andAlso()
                            ->whereLessThanOrEqual("capacity", 2000)
                        ->closeSubclause()
                        ->withinRadiusOf("coordinates", 6.0, 38.96939, -77.386398)
                        ->orderByDescending("date")
                        ->toList();

                $this->assertEquals(2, $queryStats->getTotalResults());

                $venues = array_map(function($x) { return $x->getVenue();}, $events);
                $this->assertEquals(["c/3", "b/2"], $venues);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_can_do_spatial_search_with_client_api_add_order(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new SpatialIdx())->execute($store);

            $session = $store->openSession();
            try {
                $session->store(new Event("a/1", 38.9579000, -77.3572000));
                $session->store(new Event("b/1", 38.9579000, -77.3572000));
                $session->store(new Event("c/1", 38.9579000, -77.3572000));
                $session->store(new Event("a/2", 38.9690000, -77.3862000));
                $session->store(new Event("b/2", 38.9690000, -77.3862000));
                $session->store(new Event("c/2", 38.9690000, -77.3862000));
                $session->store(new Event("a/3", 38.9510000, -77.4107000));
                $session->store(new Event("b/3", 38.9510000, -77.4107000));
                $session->store(new Event("c/3", 38.9510000, -77.4107000));
                $session->store(new Event("d/1", 37.9510000, -77.4107000));
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $events = $session->query(Event::class, Query::index("spatialIdx"))
                        ->withinRadiusOf("coordinates", 6.0, 38.96939, -77.386398)
                        ->orderByDistance("coordinates", 38.96939, -77.386398)
                        ->addOrder("venue", false)
                        ->toList();

                $venues = array_map(function($x) { return $x->getVenue();}, $events);
                $this->assertEquals(["a/2", "b/2", "c/2", "a/1", "b/1", "c/1", "a/3", "b/3", "c/3"], $venues);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $events = $session->query(Event::class, Query::index("spatialIdx"))
                        ->withinRadiusOf("coordinates", 6.0, 38.96939, -77.386398)
                        ->addOrder("venue", false)
                        ->orderByDistance("coordinates", 38.96939, -77.386398)
                        ->toList();

                $venues = array_map(function($x) { return $x->getVenue();}, $events);
                $this->assertEquals(["a/1", "a/2", "a/3", "b/1", "b/2", "b/3", "c/1", "c/2", "c/3"], $venues);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
