<?php

namespace tests\RavenDB\Documents\Operations\Indexes\IndexOperationsTest;

use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Indexes\IndexErrorsArray;
use RavenDB\Documents\Indexes\IndexingStatus;
use RavenDB\Documents\Indexes\IndexLockMode;
use RavenDB\Documents\Indexes\IndexPriority;
use RavenDB\Documents\Indexes\IndexStats;
use RavenDB\Documents\Operations\Indexes\DeleteIndexOperation;
use RavenDB\Documents\Operations\Indexes\DisableIndexOperation;
use RavenDB\Documents\Operations\Indexes\EnableIndexOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexErrorsOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexesOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexesStatisticsOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexingStatusOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexNamesOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexStatisticsOperation;
use RavenDB\Documents\Operations\Indexes\GetTermsOperation;
use RavenDB\Documents\Operations\Indexes\IndexHasChangedOperation;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;
use RavenDB\Documents\Operations\Indexes\SetIndexesLockOperation;
use RavenDB\Documents\Operations\Indexes\SetIndexesPriorityOperation;
use RavenDB\Documents\Operations\Indexes\StartIndexingOperation;
use RavenDB\Documents\Operations\Indexes\StartIndexOperation;
use RavenDB\Documents\Operations\Indexes\StopIndexingOperation;
use RavenDB\Documents\Operations\Indexes\StopIndexOperation;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringSet;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

// !status: DONE
class IndexOperationsTest extends RemoteTestBase
{
    public function testCanDeleteIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new UsersIndex())->execute($store);

            $indexNames = $store->maintenance()->send(new GetIndexNamesOperation(0, 10));

            $this->assertContains("UsersIndex", $indexNames);

            $store->maintenance()->send(new DeleteIndexOperation("UsersIndex"));

            $indexNames = $store->maintenance()->send(new GetIndexNamesOperation(0, 10));

            $this->assertEmpty($indexNames);
        } finally {
            $store->close();
        }
    }

    public function testCanDisableAndEnableIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new UsersIndex())->execute($store);

            $store->maintenance()->send(new DisableIndexOperation("UsersIndex"));

            /** @var IndexingStatus $indexingStatus */
            $indexingStatus = $store->maintenance()->send(new GetIndexingStatusOperation());

            $indexStatus = $indexingStatus->getIndexes()[0];
            $this->assertTrue($indexStatus->getStatus()->isDisabled());

            $store->maintenance()->send(new EnableIndexOperation("UsersIndex"));

            /** @var IndexingStatus $indexingStatus */
            $indexingStatus = $store->maintenance()->send(new GetIndexingStatusOperation());

            $this->assertTrue($indexingStatus->getStatus()->isRunning());

            /** @var IndexingStatus $indexingStatus */
            $indexingStatus = $store->maintenance()->send(new GetIndexingStatusOperation());

            $indexStatus = $indexingStatus->getIndexes()[0];
            $this->assertTrue($indexStatus->getStatus()->isRunning());
        } finally {
            $store->close();
        }
    }

    public function testGetCanIndexes(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new UsersIndex())->execute($store);

            $indexDefinitions = $store->maintenance()->send(new GetIndexesOperation(0, 10));
            $this->assertCount(1, $indexDefinitions);

        } finally {
            $store->close();
        }
    }

    public function testGetCanIndexesStats(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new UsersIndex())->execute($store);

            $indexStats = $store->maintenance()->send(new GetIndexesStatisticsOperation());

            $this->assertCount(1, $indexStats);
        } finally {
            $store->close();
        }
    }

    public function testGetTerms(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new UsersIndex())->execute($store);

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Marcin");
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store, $store->getDatabase());

            /** @var StringArray $terms */
            $terms = $store->maintenance()->send(new GetTermsOperation("UsersIndex", "name", null));

            $this->assertCount(1, $terms);
            $this->assertContains("marcin", $terms->getArrayCopy());
        } finally {
            $store->close();
        }
    }

    public function testHasIndexChanged(): void
    {
        $store = $this->getDocumentStore();
        try {
            $index = new UsersIndex();
            $indexDef = $index->createIndexDefinition();

            $store->maintenance()->send(new PutIndexesOperation($indexDef));

            $this->assertFalse($store->maintenance()->send(new IndexHasChangedOperation($indexDef)));

            $indexDef->setMaps(StringSet::fromArray(["from users"]));
            $this->assertTrue($store->maintenance()->send(new IndexHasChangedOperation($indexDef)));
        } finally {
            $store->close();
        }
    }

    public function testCanStopStartIndexing(): void
    {
        $store = $this->getDocumentStore();
        try {
            $index = new UsersIndex();
            $indexDef = $index->createIndexDefinition();

            $store->maintenance()->send(new PutIndexesOperation($indexDef));

            $store->maintenance()->send(new StopIndexingOperation());

            /** @var IndexingStatus $indexingStatus */
            $indexingStatus = $store->maintenance()->send(new GetIndexingStatusOperation());

            $this->assertTrue($indexingStatus->getStatus()->isPaused());

            $store->maintenance()->send(new StartIndexingOperation());

            /** @var IndexingStatus $indexingStatus */
            $indexingStatus = $store->maintenance()->send(new GetIndexingStatusOperation());

            $this->assertTrue($indexingStatus->getStatus()->isRunning());
        } finally {
            $store->close();
        }
    }

    public function testCanStopStartIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $index = new UsersIndex();
            $indexDef = $index->createIndexDefinition();

            $store->maintenance()->send(new PutIndexesOperation($indexDef));

            $store->maintenance()->send(new StopIndexOperation($indexDef->getName()));

            /** @var IndexingStatus $indexingStatus */
            $indexingStatus = $store->maintenance()->send(new GetIndexingStatusOperation());

            $this->assertTrue($indexingStatus->getStatus()->isRunning());
            $this->assertTrue($indexingStatus->getIndexes()[0]->getStatus()->isPaused());

            $store->maintenance()->send(new StartIndexOperation($indexDef->getName()));

            /** @var IndexingStatus $indexingStatus */
            $indexingStatus = $store->maintenance()->send(new GetIndexingStatusOperation());

            $this->assertTrue($indexingStatus->getStatus()->isRunning());
            $this->assertTrue($indexingStatus->getIndexes()[0]->getStatus()->isRunning());
        } finally {
            $store->close();
        }
    }

    public function testCanSetIndexLockMode(): void
    {
        $store = $this->getDocumentStore();
        try {
            $index = new UsersIndex();
            $indexDef = $index->createIndexDefinition();

            $store->maintenance()->send(new PutIndexesOperation($indexDef));

            $store->maintenance()->send(new SetIndexesLockOperation($indexDef->getName(), IndexLockMode::lockedError()));
            /** @var IndexDefinition $newIndexDef */
            $newIndexDef = $store->maintenance()->send(new GetIndexOperation($indexDef->getName()));

            $this->assertTrue($newIndexDef->getLockMode()->isLockedError());
        } finally {
            $store->close();
        }
    }

    public function testCanSetIndexPriority(): void
    {
        $store = $this->getDocumentStore();
        try {
            $index = new UsersIndex();
            $indexDef = $index->createIndexDefinition();

            $store->maintenance()->send(new PutIndexesOperation($indexDef));

            $store->maintenance()->send(new SetIndexesPriorityOperation($indexDef->getName(), IndexPriority::high()));

            /** @var IndexDefinition $newIndexDef */
            $newIndexDef = $store->maintenance()->send(new GetIndexOperation($indexDef->getName()));

            $this->assertTrue($newIndexDef->getPriority()->isHigh());
        } finally {
            $store->close();
        }
    }

    public function testCanListErrors(): void
    {
        $store = $this->getDocumentStore();
        try {
            $index = new UsersInvalidIndex();
            $indexDef = $index->createIndexDefinition();

            $store->maintenance()->send(new PutIndexesOperation($indexDef));

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName(null);
                $user->setAge(0);
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store, $store->getDatabase());

            $this->waitForValue(function () use ($store) {
                return count($store->maintenance()->send(new GetIndexErrorsOperation())[0]->getErrors());
            }, 1);

            $this->waitForValue(function () use ($store, $indexDef) {
                return count($store->maintenance()->send(new GetIndexErrorsOperation([$indexDef->getName()]))[0]->getErrors());
            }, 1);

            /** @var IndexErrorsArray $indexErrors */
            $indexErrors = $store->maintenance()->send(new GetIndexErrorsOperation());
            /** @var IndexErrorsArray $indexErrors */
            $perIndexErrors = $store->maintenance()->send(new GetIndexErrorsOperation(StringArray::fromArray([$indexDef->getName()])));

            $this->assertCount(1, $indexErrors);

            $this->assertCount(1, $perIndexErrors);

            $this->assertCount(1, $indexErrors[0]->getErrors());
            $this->assertCount(1, $perIndexErrors[0]->getErrors());
        } finally {
            $store->close();
        }
    }

    public function testCanGetIndexStatistics(): void
    {
        $store = $this->getDocumentStore();
        try {
            $index = new Users_Index();
            $indexDef = $index->createIndexDefinition();

            $store->maintenance()->send(new PutIndexesOperation($indexDef));

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName(null);
                $user->setAge(0);
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store, $store->getDatabase());

            /** @var IndexStats $stats */
            $stats = $store->maintenance()->send(new GetIndexStatisticsOperation($indexDef->getName()));
            $this->assertEquals(1, $stats->getEntriesCount());
        } finally {
            $store->close();
        }
    }
}
