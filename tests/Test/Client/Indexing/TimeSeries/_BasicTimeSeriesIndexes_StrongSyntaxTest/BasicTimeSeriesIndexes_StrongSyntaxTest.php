<?php

namespace tests\RavenDB\Test\Client\Indexing\TimeSeries\_BasicTimeSeriesIndexes_StrongSyntaxTest;

use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RavenTestHelper;
use tests\RavenDB\RemoteTestBase;

class BasicTimeSeriesIndexes_StrongSyntaxTest extends RemoteTestBase
{
    public function testBasicMapIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $now1 = RavenTestHelper::utcToday();

            $session = $store->openSession();
            try {
                $company = new Company();
                $session->store($company, "companies/1");
                $session->timeSeriesFor($company, "HeartRate")
                    ->append($now1, 7, "tag");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $timeSeriesIndex = new MyTsIndex();
            $indexDefinition = $timeSeriesIndex->createIndexDefinition();

            $this->assertNotNull($indexDefinition);

            $timeSeriesIndex->execute($store);

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $results = $session->query(MyMultiMapTsIndexResult::class, MyTsIndex::class)
                    ->toList();

                $this->assertCount(1, $results);

                /** @var MyMultiMapTsIndexResult $result */
                $result = $results[0];

                $this->assertEquals($now1, $result->getDate());
                $this->assertNotNull($result->getUser());
                $this->assertGreaterThan(0, $result->getHeartBeat());
            } finally {
                $session->close();
            }
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

                $session->timeSeriesFor($company, "HeartRate")
                        ->append($now, 2.5, "tag1");
                $session->timeSeriesFor($company, "HeartRate2")
                        ->append($now, 3.5, "tag2");

                $user = new User();
                $session->store($user);
                $session->timeSeriesFor($user, "HeartRate")
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

                /** @var MyMultiMapTsIndexResult $result */
                $result = $results[0];

                $this->assertEquals($now, $result->getDate());
                $this->assertNotNull($result->getUser());
                $this->assertGreaterThan(0, $result->getHeartBeat());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

}
