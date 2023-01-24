<?php

namespace tests\RavenDB\Test\Client\Indexing\_BasicTimeSeriesIndexes_JavaScript;

use RavenDB\Documents\Operations\Indexes\GetTermsOperation;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RavenTestHelper;
use tests\RavenDB\RemoteTestBase;

class BasicTimeSeriesIndexes_JavaScript extends RemoteTestBase
{
//    public function basicMapIndexWithLoad(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            Date now1 = new Date();
//            Date now2 = DateUtils.addSeconds(now1, 1);
//
//            try (IDocumentSession session = store.openSession()) {
//                Employee employee = new Employee();
//                employee.setFirstName("John");
//
//                session.store(employee, "employees/1");
//
//                Company company = new Company();
//                session.store(company, "companies/1");
//
//                session.timeSeriesFor(company, "HeartRate")
//                        .append(now1, 7.0, employee.getId());
//
//                Company company2 = new Company();
//                session.store(company2, "companies/11");
//
//                session.timeSeriesFor(company2, "HeartRate")
//                        .append(now1, 11.0, employee.getId());
//
//                session.saveChanges();
//            }
//
//            MyTsIndex_Load timeSeriesIndex = new MyTsIndex_Load();
//            String indexName = timeSeriesIndex.getIndexName();
//            TimeSeriesIndexDefinition indexDefinition = timeSeriesIndex.createIndexDefinition();
//
//            timeSeriesIndex.execute(store);
//
//            waitForIndexing(store);
//
//            String[] terms = store.maintenance().send(new GetTermsOperation(indexName, "employee", null));
//            assertThat(terms)
//                    .hasSize(1)
//                    .contains("john");
//
//            try (IDocumentSession session = store.openSession()) {
//                Employee employee = session.load(Employee.class, "employees/1");
//                employee.setFirstName("Bob");
//                session.saveChanges();
//            }
//
//            waitForIndexing(store);
//
//            terms = store.maintenance().send(new GetTermsOperation(indexName, "employee", null));
//            assertThat(terms)
//                    .hasSize(1)
//                    .contains("bob");
//
//            try (IDocumentSession session = store.openSession()) {
//                session.delete("employees/1");
//                session.saveChanges();
//            }
//
//            waitForIndexing(store);
//
//            terms = store.maintenance().send(new GetTermsOperation(indexName, "employee", null));
//            assertThat(terms)
//                    .hasSize(0);
//        }
//    }
//
//    @Test
//    public function basicMapReduceIndexWithLoad(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            Date today = RavenTestHelper.utcToday();
//
//            try (IDocumentSession session = store.openSession()) {
//                Address address = new Address();
//                address.setCity("NY");
//
//                session.store(address, "addresses/1");
//
//                User user = new User();
//                user.setAddressId(address.getId());
//
//                session.store(user, "users/1");
//
//                for (int i = 0; i < 10; i++) {
//                    session.timeSeriesFor(user, "heartRate")
//                            .append(DateUtils.addHours(today, i), 180 + i, address.getId());
//                }
//
//                session.saveChanges();
//            }
//
//            AverageHeartRateDaily_ByDateAndCity timeSeriesIndex = new AverageHeartRateDaily_ByDateAndCity();
//            String indexName = timeSeriesIndex.getIndexName();
//            TimeSeriesIndexDefinition indexDefinition = timeSeriesIndex.createIndexDefinition();
//
//            timeSeriesIndex.execute(store);
//
//            waitForIndexing(store);
//
//            String[] terms = store.maintenance().send(new GetTermsOperation(indexName, "heartBeat", null));
//            assertThat(terms)
//                    .hasSize(1)
//                    .contains("184.5");
//
//            terms = store.maintenance().send(new GetTermsOperation(indexName, "date", null));
//            assertThat(terms)
//                    .hasSize(1);
//
//            terms = store.maintenance().send(new GetTermsOperation(indexName, "city", null));
//            assertThat(terms)
//                    .hasSize(1)
//                    .contains("ny");
//
//            terms = store.maintenance().send(new GetTermsOperation(indexName, "count", null));
//            assertThat(terms)
//                    .hasSize(1);
//            assertThat(terms[0])
//                    .isEqualTo("10");
//
//            try (IDocumentSession session = store.openSession()) {
//                Address address = session.load(Address.class, "addresses/1");
//                address.setCity("LA");
//                session.saveChanges();
//            }
//
//            waitForIndexing(store);
//
//            terms = store.maintenance().send(new GetTermsOperation(indexName, "city", null));
//            assertThat(terms)
//                    .hasSize(1)
//                    .contains("la");
//        }
//    }
//
//    @Test
//    public function canMapAllTimeSeriesFromCollection(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            Date now1 = new Date();
//            Date now2 = DateUtils.addSeconds(now1, 1);
//
//            try (IDocumentSession session = store.openSession()) {
//                Company company = new Company();
//                session.store(company, "companies/1");
//                session.timeSeriesFor(company, "heartRate")
//                        .append(now1, 7.0, "tag1");
//                session.timeSeriesFor(company, "likes")
//                        .append(now1, 3.0, "tag2");
//
//                session.saveChanges();
//            }
//
//            new MyTsIndex_AllTimeSeries().execute(store);
//
//            waitForIndexing(store);
//
//            String[] terms = store.maintenance().send(new GetTermsOperation("MyTsIndex/AllTimeSeries", "heartBeat", null));
//            assertThat(terms)
//                    .hasSize(2)
//                    .contains("7")
//                    .contains("3");
//        }
//    }
//
//    @Test
//    public function basicMultiMapIndex(): void {
//        Date now = RavenTestHelper.utcToday();
//
//        try (IDocumentStore store = getDocumentStore()) {
//            MyMultiMapTsIndex timeSeriesIndex = new MyMultiMapTsIndex();
//            timeSeriesIndex.execute(store);
//
//            try (IDocumentSession session = store.openSession()) {
//                Company company = new Company();
//                session.store(company);
//
//                session.timeSeriesFor(company, "heartRate")
//                        .append(now, 2.5, "tag1");
//                session.timeSeriesFor(company, "heartRate2")
//                        .append(now, 3.5, "tag2");
//
//                User user = new User();
//                session.store(user);
//                session.timeSeriesFor(user, "heartRate")
//                        .append(now, 4.5, "tag3");
//
//                session.saveChanges();
//            }
//
//            waitForIndexing(store);
//
//            try (IDocumentSession session = store.openSession()) {
//                List<MyMultiMapTsIndex.Result> results = session.query(MyMultiMapTsIndex.Result.class, MyMultiMapTsIndex.class)
//                        .toList();
//
//                assertThat(results)
//                        .hasSize(3);
//            }
//        }
//    }

    public function testTimeSeriesNamesFor(): void
    {
        $now = RavenTestHelper::utcToday();

        $store = $this->getDocumentStore();
        try {

            $index = new Companies_ByTimeSeriesNames();
            $index->execute($store);

            $session = $store->openSession();
            try {
                $company = new Company();
                $session->store($company, "companies/1");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            RavenTestHelper::assertNoIndexErrors($store);

            $terms = $store->maintenance()->send(new GetTermsOperation($index->getIndexName(), "names", null));
            $this->assertCount(0, $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation($index->getIndexName(), "names_IsArray", null));
            $this->assertCount(1, $terms);
            $this->assertContains("true", $terms);

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");
                $session->timeSeriesFor($company, "heartRate")
                        ->append($now, 2.5, "tag1");
                $session->timeSeriesFor($company, "heartRate2")
                        ->append($now, 3.5, "tag2");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            RavenTestHelper::assertNoIndexErrors($store);

            $terms = $store->maintenance()->send(new GetTermsOperation($index->getIndexName(), "names", null));
            $this->assertCount(2, $terms);
            $this->assertContains("heartrate", $terms);
            $this->assertContains("heartrate2", $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation($index->getIndexName(), "names_IsArray", null));
            $this->assertCount(1, $terms);
            $this->assertContains("true", $terms);
        } finally {
            $store->close();
        }
    }
}
