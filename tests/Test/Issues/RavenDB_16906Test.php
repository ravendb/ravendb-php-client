<?php

namespace tests\RavenDB\Test\Issues;

use Exception;
use RavenDB\Exceptions\IllegalArgumentException;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RavenDB_16906Test extends RemoteTestBase
{
    public function testTimeSeriesFor_ShouldThrowBetterError_OnNullEntity(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1");

                try {
                    $session->timeSeriesFor($user, "heartRate");

                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IllegalArgumentException::class, $exception);
                    $this->assertStringContainsString("entity cannot be null", $exception->getMessage());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
