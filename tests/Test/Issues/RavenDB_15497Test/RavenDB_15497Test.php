<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15497Test;

use Exception;
use RavenDB\Documents\Operations\Indexes\StopIndexOperation;
use RavenDB\Exceptions\RavenTimeoutException;
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

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("user1");
                $user->setCount(3);

                $session->store($user);

                $session->advanced()->waitForIndexesAfterSaveChanges(function($x) {
                    $x->withTimeout(Duration::ofSeconds(3));
                    $x->throwOnTimeout(false);
                });
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $store->maintenance()->send(new StopIndexOperation($index->getIndexName()));

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
                    $this->assertInstanceOf(RavenTimeoutException::class, $exception);
                    $this->assertStringContainsString("RavenTimeoutException", $exception->getMessage());
                    $this->assertStringContainsString("could not verify that", $exception->getMessage());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
