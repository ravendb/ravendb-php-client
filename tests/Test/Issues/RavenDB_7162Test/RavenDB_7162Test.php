<?php

namespace tests\RavenDB\Test\Issues\RavenDB_7162Test;

use Exception;
use RavenDB\Documents\Operations\Indexes\StopIndexingOperation;
use RavenDB\Exceptions\RavenException;
use RavenDB\Type\Duration;
use tests\RavenDB\DatabaseCommands;
use tests\RavenDB\Infrastructure\Entity\Person;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RavenDB_7162Test extends RemoteTestBase
{
    public function testRequestTimeoutShouldWork(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->maintenance()->send(new StopIndexingOperation());

            $session = $store->openSession();
            try {
                $person = new Person();
                $person->setName("John");
                $session->store($person);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $withTimeout = $store->setRequestTimeout(Duration::ofMillis(100));
            try {
                $commands = DatabaseCommands::forStore($store);
                try {
                    try {
                        $commands->execute(new DelayCommand(Duration::ofSeconds(2)));

                        throw new Exception('It should throw exception before reaching this code');
                    } catch (Throwable $exception) {
                        $this->assertInstanceOf(RavenException::class, $exception);
                        $this->assertStringContainsString("failed with timeout after 00:00:00.1000000", $exception->getMessage());
                    }
                } finally {
                    $commands->close();
                }
            } finally {
                $withTimeout->close();
            }
        } finally {
            $store->close();
        }
    }

    /** @doesNotPerformAssertions */
    public function testRequestWithTimeoutShouldWork(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->maintenance()->send(new StopIndexingOperation());

            $session = $store->openSession();
            try {
                $person = new Person();
                $person->setName("John");
                $session->store($person);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $withTimeout = $store->setRequestTimeout(Duration::ofMillis(100));
            try {
                $commands = DatabaseCommands::forStore($store);
                try {
                    $commands->execute(new DelayCommand(Duration::ofMillis(2)));
                } finally {
                    $commands->close();
                }
            } finally {
                $withTimeout->close();
            }
        } finally {
            $store->close();
        }
    }
}
