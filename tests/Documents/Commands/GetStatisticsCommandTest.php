<?php

namespace tests\RavenDB\Documents\Commands;

use RavenDB\Documents\Operations\DatabaseStatistics;
use RavenDB\Documents\Operations\GetStatisticsCommand;
use RavenDB\Documents\Operations\GetStatisticsOperation;
use RavenDB\Documents\Operations\IndexInformation;
use RavenDB\Documents\Smuggler\DatabaseItemType;
use RavenDB\Documents\Smuggler\DatabaseItemTypeSet;
use tests\RavenDB\Infrastructure\CreateSampleDataOperation;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class GetStatisticsCommandTest extends RemoteTestBase
{
    public function testCanGetStats(): void
    {
        $store = $this->getDocumentStore();
        try {
            $executor = $store->getRequestExecutor();

            $sampleData = new CreateSampleDataOperation(
                DatabaseItemTypeSet::fromArray([
                    DatabaseItemType::documents(),
                    DatabaseItemType::indexes(),
                    DatabaseItemType::attachments(),
                    DatabaseItemType::revisionDocuments()
                ])
            );

            $store->maintenance()->send($sampleData);

            $this->waitForIndexing($store, $store->getDatabase(), null);

            $command = new GetStatisticsCommand();
            $executor->execute($command);

            /** @var DatabaseStatistics $stats */
            $stats = $command->getResult();
            $this->assertNotNull($stats);

            $this->assertNotNull($stats->getLastDocEtag());
            $this->assertGreaterThan(0, $stats->getLastDocEtag());

            $this->assertGreaterThanOrEqual(3, $stats->getCountOfIndexes());

            $this->assertEquals(1059, $stats->getCountOfDocuments());

            $this->assertGreaterThan(0,  $stats->getCountOfRevisionDocuments());

            $this->assertEquals(0, $stats->getCountOfDocumentsConflicts());

            $this->assertEquals(0, $stats->getCountOfConflicts());

            $this->assertEquals(17, $stats->getCountOfUniqueAttachments());

            $this->assertNotEmpty($stats->getDatabaseChangeVector());

            $this->assertNotEmpty($stats->getDatabaseId());

            $this->assertNotEmpty($stats->getPager());

            $this->assertNotNull($stats->getLastIndexingTime());

            $this->assertNotNull($stats->getIndexes());
            $this->assertNotNull($stats->getSizeOnDisk()->getHumaneSize());
            $this->assertNotNull($stats->getSizeOnDisk()->getSizeInBytes());

            /** @var IndexInformation $indexInformation */
            foreach ($stats->getIndexes() as $indexInformation) {
                $this->assertNotNull($indexInformation->getName());
                $this->assertFalse($indexInformation->isStale());
                $this->assertNotNull($indexInformation->getState());
                $this->assertNotNull($indexInformation->getLockMode());
                $this->assertNotNull($indexInformation->getPriority());
                $this->assertNotNull($indexInformation->getType());
                $this->assertNotNull($indexInformation->getLastIndexingTime());
            }
        } finally {
            $store->close();
        }
    }

    /** @todo: active test method when counters and time series are implemented */
    public function atestCanGetStatsForCountersAndTimeSeries(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $session->store(new User(), "users/1");

//                $session->countersFor("users/1")
//                        ->increment("c1");
//
//                $session->countersFor("users/1")
//                        ->increment("c2");
//
//                /** @var SessionDocumentTimeSeriesInterface $tsf */
//                $tsf = $session->timeSeriesFor("users/1", "Heartrate");
//                $tsf->append(DateUtils::now(), 70);
//                $tsf->append(DateUtils::addMinutes(DateUtils::now(), 1), 20);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DatabaseStatistics $statistics */
            $statistics = $store->maintenance()->send(new GetStatisticsOperation());

            $this->assertEquals(1, $statistics->getCountOfCounterEntries());
            $this->assertEquals(1, $statistics->getCountOfTimeSeriesSegments());
        } finally {
            $store->close();
        }
    }
}
