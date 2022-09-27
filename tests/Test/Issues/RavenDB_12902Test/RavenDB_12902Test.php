<?php

namespace tests\RavenDB\Test\Issues\RavenDB_12902Test;

use RavenDB\Documents\Queries\Facets\FacetResult;
use RavenDB\Documents\Session\QueryStatistics;
use RavenDB\Utils\AtomicInteger;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_12902Test extends RemoteTestBase
{
    public function testAfterAggregationQueryExecutedShouldBeExecutedOnlyOnce(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new UsersByName());

            $session = $store->openSession();
            try {
                $counter = new AtomicInteger();

                $stats = new QueryStatistics();

                /** @var array<FacetResult> $results */
                $results = $session->query(User::class, UsersByName::class)
                        ->statistics($stats)
                        ->addAfterQueryExecutedListener(function($x) use ($counter) {
                            $counter->incrementAndGet();
                        })
                        ->whereEquals("name", "Doe")
                        ->aggregateBy(function($x) { return $x->byField("name")->sumOn("count"); } )
                        ->execute();

                $this->assertCount(1, $results);

                $this->assertCount(0, $results["name"]->getValues());

                $this->assertNotNull($stats);
                $this->assertEquals(1, $counter->get());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

//    public function afterSuggestionQueryExecutedShouldBeExecutedOnlyOnce(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndex(new UsersByName());
//
//            try (IDocumentSession session = store.openSession()) {
//                AtomicInteger counter = new AtomicInteger();
//
//                Reference<QueryStatistics> stats = new Reference<>();
//
//                Map<String, SuggestionResult> results = session.query(User.class, UsersByName.class)
//                        .statistics(stats)
//                        .addAfterQueryExecutedListener(x -> {
//                            counter.incrementAndGet();
//                        })
//                        .suggestUsing(x -> x.byField("name", "Orin"))
//                        .execute();
//
//                assertThat(results)
//                        .hasSize(1);
//                assertThat(results.get("name").getSuggestions().size())
//                        .isZero();
//                assertThat(stats.value)
//                        .isNotNull();
//                assertThat(counter.get())
//                        .isOne();
//            }
//        }
//    }
//
//    @Test
//    public function afterLazyQueryExecutedShouldBeExecutedOnlyOnce(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                AtomicInteger counter = new AtomicInteger();
//
//                Reference<QueryStatistics> stats = new Reference<>();
//
//                List<User> results = session.query(User.class)
//                        .addAfterQueryExecutedListener(x -> {
//                            counter.incrementAndGet();
//                        })
//                        .statistics(stats)
//                        .whereEquals("name", "Doe")
//                        .lazily()
//                        .getValue();
//
//                assertThat(results)
//                        .isEmpty();
//                assertThat(stats.value)
//                        .isNotNull();
//                assertThat(counter.get())
//                        .isOne();
//            }
//        }
//    }
//
//    @Test
//    public function afterLazyAggregationQueryExecutedShouldBeExecutedOnlyOnce(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndex(new UsersByName());
//
//            try (IDocumentSession session = store.openSession()) {
//                AtomicInteger counter = new AtomicInteger();
//
//                Reference<QueryStatistics> stats = new Reference<>();
//
//                Map<String, FacetResult> results = session.query(User.class, UsersByName.class)
//                        .statistics(stats)
//                        .addAfterQueryExecutedListener(x -> {
//                            counter.incrementAndGet();
//                        })
//                        .whereEquals("name", "Doe")
//                        .aggregateBy(x -> x.byField("name").sumOn("count"))
//                        .executeLazy()
//                        .getValue();
//
//                assertThat(results)
//                        .hasSize(1);
//                assertThat(results.get("name").getValues().size())
//                        .isZero();
//                assertThat(stats.value)
//                        .isNotNull();
//                assertThat(counter.get())
//                        .isOne();
//            }
//        }
//    }
//
//    @Test
//    public function afterLazySuggestionQueryExecutedShouldBeExecutedOnlyOnce(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndex(new UsersByName());
//
//            try (IDocumentSession session = store.openSession()) {
//                AtomicInteger counter = new AtomicInteger();
//
//                Reference<QueryStatistics> stats = new Reference<>();
//
//                Map<String, SuggestionResult> results = session
//                        .query(User.class, UsersByName.class)
//                        .addAfterQueryExecutedListener(r -> {
//                            counter.incrementAndGet();
//                        })
//                        .statistics(stats)
//                        .suggestUsing(x -> x.byField("name", "Orin"))
//                        .executeLazy()
//                        .getValue();
//
//                assertThat(results)
//                        .hasSize(1);
//                assertThat(results.get("name").getSuggestions().size())
//                        .isZero();
//                assertThat(stats.value)
//                        .isNotNull();
//                assertThat(counter.get())
//                        .isOne();
//            }
//        }
//    }

    public function testCanGetValidStatisticsInAggregationQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new UsersByName());

            $session = $store->openSession();
            try {
                $stats = new QueryStatistics();
                $session->query(User::class, UsersByName::class)
                        ->statistics($stats)
                        ->whereEquals("name", "Doe")
                        ->aggregateBy(function($x) { return $x->byField("name")->sumOn("count");})
                        ->execute();

                $this->assertNotNull($stats->getIndexName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

//    public function canGetValidStatisticsInSuggestionQuery(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndex(new UsersByName());
//
//            try (IDocumentSession session = store.openSession()) {
//                Reference<QueryStatistics> stats = new Reference<>();
//                session
//                        .query(User.class, UsersByName.class)
//                        .statistics(stats)
//                        .suggestUsing(x -> x.byField("name", "Orin"))
//                        .execute();
//
//                assertThat(stats.value.getIndexName())
//                        .isNotNull();
//            }
//        }
//    }
}
