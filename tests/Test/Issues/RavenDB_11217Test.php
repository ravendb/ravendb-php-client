<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Session\QueryStatistics;
use RavenDB\Documents\Session\SessionOptions;
use tests\RavenDB\Infrastructure\Orders\Product;
use tests\RavenDB\RemoteTestBase;

class RavenDB_11217Test extends RemoteTestBase
{
//    public void sessionWideNoTrackingShouldWork() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                Supplier supplier = new Supplier();
//                supplier.setName("Supplier1");
//
//                session.store(supplier);
//
//                Product product = new Product();
//                product.setName("Product1");
//                product.setSupplier(supplier.getId());
//
//                session.store(product);
//                session.saveChanges();
//            }
//
//            SessionOptions noTrackingOptions = new SessionOptions();
//            noTrackingOptions.setNoTracking(true);
//
//            try (IDocumentSession session = store.openSession(noTrackingOptions)) {
//                Supplier supplier = new Supplier();
//                supplier.setName("Supplier2");
//
//                assertThatThrownBy(() -> session.store(supplier))
//                        .isInstanceOf(IllegalStateException.class);
//            }
//
//            try (IDocumentSession session = store.openSession(noTrackingOptions)) {
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isZero();
//
//                Product product1 = session.load(Product.class, "products/1-A", b -> b.includeDocuments("supplier"));
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isOne();
//
//                assertThat(product1)
//                        .isNotNull();
//                assertThat(product1.getName())
//                        .isEqualTo("Product1");
//                assertThat(session.advanced().isLoaded(product1.getId()))
//                        .isFalse();
//                assertThat(session.advanced().isLoaded(product1.getSupplier()))
//                        .isFalse();
//
//                Supplier supplier = session.load(Supplier.class, product1.getSupplier());
//                assertThat(supplier)
//                        .isNotNull();
//                assertThat(supplier.getName())
//                        .isEqualTo("Supplier1");
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(2);
//                assertThat(session.advanced().isLoaded(supplier.getId()))
//                        .isFalse();
//
//                Product product2 = session.load(Product.class, "products/1-A", b -> b.includeDocuments("supplier"));
//                assertThat(product1)
//                        .isNotSameAs(product2);
//            }
//
//            try (IDocumentSession session = store.openSession(noTrackingOptions)) {
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isZero();
//
//                Product product1 = session
//                        .advanced()
//                        .loadStartingWith(Product.class, "products/")[0];
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isOne();
//
//                assertThat(product1)
//                        .isNotNull();
//                assertThat(product1.getName())
//                        .isEqualTo("Product1");
//                assertThat(session.advanced().isLoaded(product1.getId()))
//                        .isFalse();
//                assertThat(session.advanced().isLoaded(product1.getSupplier()))
//                        .isFalse();
//
//                Supplier supplier = session.advanced().loadStartingWith(Supplier.class, product1.getSupplier())[0];
//
//                assertThat(supplier)
//                        .isNotNull();
//                assertThat(supplier.getName())
//                        .isEqualTo("Supplier1");
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(2);
//                assertThat(session.advanced().isLoaded(supplier.getId()))
//                        .isFalse();
//
//                Product product2 = session
//                        .advanced()
//                        .loadStartingWith(Product.class, "products/")[0];
//
//                assertThat(product1)
//                        .isNotSameAs(product2);
//            }
//
//            try (IDocumentSession session = store.openSession(noTrackingOptions)) {
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(0);
//
//                List<Product> products = session
//                        .query(Product.class)
//                        .include("supplier")
//                        .toList();
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//                assertThat(products)
//                        .hasSize(1);
//
//                Product product1 = products.get(0);
//                assertThat(product1)
//                        .isNotNull();
//                assertThat(session.advanced().isLoaded(product1.getId()))
//                        .isFalse();
//                assertThat(session.advanced().isLoaded(product1.getSupplier()))
//                        .isFalse();
//
//                Supplier supplier = session.load(Supplier.class, product1.getSupplier());
//                assertThat(supplier)
//                        .isNotNull();
//                assertThat(supplier.getName())
//                        .isEqualTo("Supplier1");
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(2);
//                assertThat(session.advanced().isLoaded(supplier.getId()))
//                        .isFalse();
//
//                products = session
//                        .query(Product.class)
//                        .include("supplier")
//                        .toList();
//
//                assertThat(products)
//                        .hasSize(1);
//
//                Product product2 = products.get(0);
//                assertThat(product1)
//                        .isNotSameAs(product2);
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                session.countersFor("products/1-A").increment("c1");
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession(noTrackingOptions)) {
//                Product product1 = session.load(Product.class, "products/1-A");
//                ISessionDocumentCounters counters = session.countersFor(product1.getId());
//
//                counters.get("c1");
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(2);
//
//                counters.get("c1");
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(3);
//
//                Map<String, Long> val1 = counters.getAll();
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(4);
//
//                Map<String, Long> val2 = counters.getAll();
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(5);
//
//                assertThat(val1)
//                        .isNotSameAs(val2);
//            }
//
//        }
//    }

    public function testSessionWideNoCachingShouldWork(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $stats = new QueryStatistics();
                $session->query(Product::class)
                        ->statistics($stats)
                        ->whereEquals("name", "HR")
                        ->toList();

                $this->assertGreaterThan(0, $stats->getDurationInMs());

                $session->query(Product::class)
                        ->statistics($stats)
                        ->whereEquals("name", "HR")
                        ->toList();

                $this->assertEquals(-1, $stats->getDurationInMs());
            } finally {
                $session->close();
            }

            $noCacheOptions = new SessionOptions();
            $noCacheOptions->setNoCaching(true);

            $session = $store->openSession($noCacheOptions);
            try {
                $stats = new QueryStatistics();
                $session->query(Product::class)
                    ->statistics($stats)
                    ->whereEquals("name", "HR")
                    ->toList();

                $this->assertGreaterThanOrEqual(0, $stats->getDurationInMs());

                $session->query(Product::class)
                        ->statistics($stats)
                        ->whereEquals("name", "HR")
                        ->toList();

                $this->assertGreaterThanOrEqual(0, $stats->getDurationInMs());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
