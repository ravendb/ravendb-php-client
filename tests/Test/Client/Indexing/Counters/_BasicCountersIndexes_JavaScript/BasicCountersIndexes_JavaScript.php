<?php

namespace tests\RavenDB\Test\Client\Indexing\Counters\_BasicCountersIndexes_JavaScript;

use RavenDB\Documents\Operations\Indexes\GetTermsOperation;
use tests\RavenDB\Infrastructure\Entity\Address;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class BasicCountersIndexes_JavaScript extends RemoteTestBase
{
    public function testBasicMapIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $session->store($company, "companies/1");
                $session->countersFor($company)->increment("heartRate", 7);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $timeSeriesIndex = new MyCounterIndex();
            $indexDefinition = $timeSeriesIndex->createIndexDefinition();

            $timeSeriesIndex->execute($store);

            $this->waitForIndexing($store);

            $terms = $store->maintenance()->send(new GetTermsOperation("MyCounterIndex", "heartBeat", null));
            $this->assertCount(1, $terms);
            $this->assertContains("7", $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation("MyCounterIndex", "user", null));
            $this->assertCount(1, $terms);
            $this->assertContains("companies/1", $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation("MyCounterIndex", "name", null));
            $this->assertCount(1, $terms);
            $this->assertContains("heartrate", $terms);

            $session = $store->openSession();
            try {
                $company1 = $session->load(Company::class, "companies/1");
                $session->countersFor($company1)
                        ->increment("heartRate", 3);

                $company2 = new Company();
                $session->store($company2, "companies/2");
                $session->countersFor($company2)
                        ->increment("heartRate", 4);

                $company3 = new Company();
                $session->store($company3, "companies/3");
                $session->countersFor($company3)
                        ->increment("heartRate", 6);

                $company999 = new Company();
                $session->store($company999, "companies/999");
                $session->countersFor($company999)
                        ->increment("heartRate_Different", 999);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $terms = $store->maintenance()->send(new GetTermsOperation("MyCounterIndex", "heartBeat", null));
            $this->assertCount(3, $terms);
            $this->assertContains("10", $terms);
            $this->assertContains("4", $terms);
            $this->assertContains("6", $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation("MyCounterIndex", "user", null));
            $this->assertCount(3, $terms);
            $this->assertContains("companies/1", $terms);
            $this->assertContains("companies/2", $terms);
            $this->assertContains("companies/3", $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation("MyCounterIndex", "name", null));
            $this->assertCount(1, $terms);
            $this->assertContains("heartrate", $terms);

            // skipped rest of the test
        } finally {
            $store->close();
        }
    }

    public function testBasicMapReduceIndexWithLoad(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                for ($i = 0; $i < 10; $i++) {
                    $address = new Address();
                    $address->setCity("NY");
                    $session->store($address, "addresses/" . $i);

                    $user = new User();
                    $user->setAddressId($address->getId());
                    $session->store($user, "users/" . $i);

                    $session->countersFor($user)
                            ->increment("heartRate", 180 + $i);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $timeSeriesIndex = new AverageHeartRate_WithLoad();
            $indexName = $timeSeriesIndex->getIndexName();
            $indexDefinition = $timeSeriesIndex->createIndexDefinition();

            $timeSeriesIndex->execute($store);

            $this->waitForIndexing($store);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "heartBeat", null));
            $this->assertCount(1, $terms);
            print_r($terms);
            $this->assertContains("184.5", $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "count", null));
            $this->assertCount(1, $terms);
            $this->assertContains("10", $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "city", null));
            $this->assertCount(1, $terms);
            $this->assertContains("ny", $terms);
        } finally {
            $store->close();
        }
    }

    public function testCanMapAllCountersFromCollection(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $session->store($company, "companies/1");
                $session->countersFor($company)
                        ->increment("heartRate", 7);
                $session->countersFor($company)
                        ->increment("likes", 3);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $timeSeriesIndex = new MyCounterIndex_AllCounters();
            $indexName = $timeSeriesIndex->getIndexName();
            $indexDefinition = $timeSeriesIndex->createIndexDefinition();

            $timeSeriesIndex->execute($store);

            $this->waitForIndexing($store);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "heartBeat", null));
            $this->assertCount(2, $terms);
            $this->assertContains("7", $terms);
            $this->assertContains("3", $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "user", null));
            $this->assertCount(1, $terms);
            $this->assertContains("companies/1", $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "name", null));
            $this->assertCount(2, $terms);
            $this->assertContains("heartrate", $terms);
            $this->assertContains("likes", $terms);
        } finally {
            $store->close();
        }
    }

    public function testBasicMultiMapIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $timeSeriesIndex = new MyMultiMapCounterIndex();
            $timeSeriesIndex->execute($store);

            $session = $store->openSession();
            try {
                $company = new Company();
                $session->store($company);

                $session->countersFor($company)
                        ->increment("heartRate", 3);
                $session->countersFor($company)
                        ->increment("heartRate2", 5);

                $user = new User();
                $session->store($user);
                $session->countersFor($user)
                        ->increment("heartRate", 2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $results = $session->query(MyMultiMapCounterIndexResult::class, MyMultiMapCounterIndex::class)
                        ->toList();

                $this->assertCount(3, $results);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCounterNamesFor(): void {
        $store = $this->getDocumentStore();
        try {
            $index = new Companies_ByCounterNames();
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

            $terms = $store->maintenance()->send(new GetTermsOperation($index->getIndexName(), "name", null));
            $this->assertCount(0, $terms);

            $terms = $store->maintenance()->send(new GetTermsOperation($index->getIndexName(), "names_IsArray", null));
            $this->assertCount(1, $terms);
            $this->assertContains("true", $terms);

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");

                $session->countersFor($company)
                        ->increment("heartRate", 3);
                $session->countersFor($company)
                        ->increment("heartRate2", 7);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

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
