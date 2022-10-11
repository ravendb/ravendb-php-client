<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15497Test;

use Exception;
use RavenDB\Documents\Indexes\IndexStats;
use RavenDB\Documents\Operations\Indexes\DisableIndexOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexStatisticsOperation;
use RavenDB\Exceptions\TimeoutException;
use RavenDB\Type\Duration;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RavenDB_15497Test extends RemoteTestBase
{
    public function testWaitForIndexesAfterSaveChangesCanExitWhenThrowOnTimeoutIsFalse(): void
    {
        $store = $this->getDocumentStore();
        try {
            $index = new Index();
            $index->execute($store);
            $store->maintenance()->send(new DisableIndexOperation($index->getIndexName()));

            /** @var IndexStats $indexStats */
            $indexStats = $store->maintenance()->send(new GetIndexStatisticsOperation($index->getIndexName()));

            $this->assertTrue($indexStats->getState()->isDisabled());
            $this->assertTrue($indexStats->getStatus()->isDisabled());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("user1");

                $session->store($user);

                $session->advanced()->waitForIndexesAfterSaveChanges(function($x) {
                    $x->withTimeout(Duration::ofSeconds(3));
                    $x->throwOnTimeout(false);
                });
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("user1");

                $session->store($user);

                $session->advanced()->waitForIndexesAfterSaveChanges(function($x) {
                    $x->withTimeout(Duration::ofSeconds(3));
                    $x->throwOnTimeout(true);
                });

                try {
                    $session->saveChanges();
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(TimeoutException::class, $exception);
                    $this->assertStringContainsString("System.TimeoutException", $exception->getMessage());
                    $this->assertStringContainsString("could not verify that 1 indexes has caught up with the changes as of etag", $exception->getMessage());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
