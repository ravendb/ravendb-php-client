<?php

namespace tests\RavenDB\Test\Client\Indexing\_BasicTimeSeriesIndexes_JavaScript;

use DateTime;
use RavenDB\Documents\Operations\Indexes\GetTermsOperation;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\Address;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\Employee;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RavenTestHelper;
use tests\RavenDB\RemoteTestBase;

class BasicTimeSeriesIndexes_JavaScript extends RemoteTestBase
{
    public function testBasicMapIndexWithLoad(): void
    {
        $store = $this->getDocumentStore();
        try {
            $now1 = new DateTime();
            $now2 = DateUtils::addSeconds($now1, 1);

            $session = $store->openSession();
            try {
                $employee = new Employee();
                $employee->setFirstName("John");

                $session->store($employee, "employees/1");

                $company = new Company();
                $session->store($company, "companies/1");

                $session->timeSeriesFor($company, "HeartRate")
                        ->append($now1, 7.0, $employee->getId());

                $company2 = new Company();
                $session->store($company2, "companies/11");

                $session->timeSeriesFor($company2, "HeartRate")
                        ->append($now1, 11.0, $employee->getId());

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $timeSeriesIndex = new MyTsIndex_Load();
            $indexName = $timeSeriesIndex->getIndexName();
            $indexDefinition = $timeSeriesIndex->createIndexDefinition();

            $timeSeriesIndex->execute($store);

            $this->waitForIndexing($store);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "employee", null));
            $this->assertCount(1, $terms);
            $this->assertContains("john", $terms);

            $session = $store->openSession();
            try {
                $employee = $session->load(Employee::class, "employees/1");
                $employee->setFirstName("Bob");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "employee", null));
            $this->assertCount(1, $terms);
            $this->assertContains("bob", $terms);

            $session = $store->openSession();
            try {
                $session->delete("employees/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "employee", null));
            $this->assertCount(0, $terms);
        } finally {
            $store->close();
        }
    }

    public function testBasicMapReduceIndexWithLoad(): void
    {
        $store = $this->getDocumentStore();
        try {
            $today = RavenTestHelper::utcToday();

            $session = $store->openSession();
            try {
                $address = new Address();
                $address->setCity("NY");

                $session->store($address, "addresses/1");

                $user = new User();
                $user->setAddressId($address->getId());

                $session->store($user, "users/1");

                for ($i = 0; $i < 10; $i++) {
                    $session->timeSeriesFor($user, "heartRate")
                            ->append(DateUtils::addHours($today, $i), 180 + $i, $address->getId());
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $timeSeriesIndex = new AverageHeartRateDaily_ByDateAndCity();
            $indexName = $timeSeriesIndex->getIndexName();
            $indexDefinition = $timeSeriesIndex->createIndexDefinition();

            $timeSeriesIndex->execute($store);

            $this->waitForIndexing($store);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "heartBeat", null));
            $this->assertCount(1, $terms);
            $this->assertContains("184.5", $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "date", null));
            $this->assertCount(1, $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "city", null));
            $this->assertCount(1, $terms);
            $this->assertContains("ny", $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "count", null));
            $this->assertCount(1, $terms);
            $this->assertEquals("10", $terms[0]);

            $session = $store->openSession();
            try {
                $address = $session->load(Address::class, "addresses/1");
                $address->setCity("LA");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "city", null));
            $this->assertCount(1, $terms);
            $this->assertContains("la", $terms);
        } finally {
            $store->close();
        }
    }

    public function testCanMapAllTimeSeriesFromCollection(): void
    {
        $store = $this->getDocumentStore();
        try {
            $now1 = new DateTime();
            $now2 = DateUtils::addSeconds($now1, 1);

            $session = $store->openSession();
            try {
                $company = new Company();
                $session->store($company, "companies/1");
                $session->timeSeriesFor($company, "heartRate")
                        ->append($now1, 7.0, "tag1");
                $session->timeSeriesFor($company, "likes")
                        ->append($now1, 3.0, "tag2");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            (new MyTsIndex_AllTimeSeries())->execute($store);

            $this->waitForIndexing($store);

            $terms = $store->maintenance()->send(new GetTermsOperation("MyTsIndex/AllTimeSeries", "heartBeat", null));
            $this->assertCount(2, $terms);
            $this->assertContains("7", $terms);
            $this->assertContains("3", $terms);
        } finally {
            $store->close();
        }
    }

    public function testBasicMultiMapIndex(): void
    {
        $now = RavenTestHelper::utcToday();

        $store = $this->getDocumentStore();
        try {
            $timeSeriesIndex = new MyMultiMapTsIndex();
            $timeSeriesIndex->execute($store);

            $session = $store->openSession();
            try {
                $company = new Company();
                $session->store($company);

                $session->timeSeriesFor($company, "heartRate")
                        ->append($now, 2.5, "tag1");
                $session->timeSeriesFor($company, "heartRate2")
                        ->append($now, 3.5, "tag2");

                $user = new User();
                $session->store($user);
                $session->timeSeriesFor($user, "heartRate")
                        ->append($now, 4.5, "tag3");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $results = $session->query(MyMultiMapTsIndexResult::class, MyMultiMapTsIndex::class)
                        ->toList();

                $this->assertCount(3, $results);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

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
