<?php

namespace tests\RavenDB\Test\Issues;

use Exception;
use Throwable;
use tests\RavenDB\RemoteTestBase;
use RavenDB\ServerWide\DatabaseRecord;
use RavenDB\ServerWide\DatabaseLockMode;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\ServerWide\DatabaseRecordWithEtag;
use RavenDB\ServerWide\GetDatabaseRecordOperation;
use RavenDB\ServerWide\Operations\DeleteDatabaseResult;
use RavenDB\ServerWide\Operations\CreateDatabaseOperation;
use RavenDB\ServerWide\Operations\DeleteDatabasesOperation;
use RavenDB\Exceptions\Database\DatabaseDoesNotExistException;
use RavenDB\Documents\Operations\ToggleDatabasesStateOperation;
use RavenDB\ServerWide\Operations\OngoingTasks\SetDatabasesLockOperation;
use RavenDB\ServerWide\Operations\OngoingTasks\SetDatabasesLockParameters;

class RavenDB_16367Test extends RemoteTestBase
{
    public function testCanLockDatabase(): void
    {
        $store = $this->getDocumentStore();
        try {
            $databaseName1 = $store->getDatabase() . "_LockMode_1";

            try {
                $lockOperation = new SetDatabasesLockOperation($databaseName1, DatabaseLockMode::preventDeletesError());
                $store->maintenance()->server()->send($lockOperation);
                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(DatabaseDoesNotExistException::class, $exception);
            }

            $store->maintenance()->server()->send(new CreateDatabaseOperation(new DatabaseRecord($databaseName1)));

            try {


                /** @var DatabaseRecordWithEtag $databaseRecord */
                $databaseRecord = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($databaseName1));
                $this->assertTrue($databaseRecord->getLockMode()->isUnlock());

                $store->maintenance()->server()->send(new SetDatabasesLockOperation($databaseName1, DatabaseLockMode::unlock()));

                /** @var DatabaseRecordWithEtag $databaseRecord */
                $databaseRecord = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($databaseName1));
                $this->assertTrue($databaseRecord->getLockMode()->isUnlock());

                $store->maintenance()->server()->send(new SetDatabasesLockOperation($databaseName1, DatabaseLockMode::preventDeletesError()));

                /** @var DatabaseRecordWithEtag $databaseRecord */
                $databaseRecord = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($databaseName1));
                $this->assertTrue($databaseRecord->getLockMode()->isPreventDeletesError());

                try {
                    $store->maintenance()->server()->send(new DeleteDatabasesOperation($databaseName1, true));
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertStringContainsString("cannot be deleted because of the set lock mode", $exception->getMessage());
                }

                $store->maintenance()->server()->send(new SetDatabasesLockOperation($databaseName1, DatabaseLockMode::preventDeletesIgnore()));

                /** @var DatabaseRecordWithEtag $databaseRecord */
                $databaseRecord = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($databaseName1));
                $this->assertTrue($databaseRecord->getLockMode()->isPreventDeletesIgnore());

                /** @var DeleteDatabaseResult $result */
                $result = $store->maintenance()->server()->send(new DeleteDatabasesOperation($databaseName1, true));
                $this->assertEquals(-1, $result->getRaftCommandIndex());

                $databaseRecord = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($databaseName1));
                $this->assertNotNull($databaseRecord);

            } finally {
                $store->maintenance()->server()->send(new SetDatabasesLockOperation($databaseName1, DatabaseLockMode::unlock()));
                /** @var DeleteDatabaseResult $result */
                $result = $store->maintenance()->server()->send(new DeleteDatabasesOperation($databaseName1, true));

                $databaseRecord = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($databaseName1));
                $this->assertNull($databaseRecord);
            }
        } finally {
            $store->close();
        }
    }

    public function testCanLockDatabase_Multiple(): void
    {
        $store = $this->getDocumentStore();
        try {
            $databaseName1 = $store->getDatabase() . "_LockMode_1";
            $databaseName2 = $store->getDatabase() . "_LockMode_2";
            $databaseName3 = $store->getDatabase() . "_LockMode_3";

            $databases = [$databaseName1, $databaseName2, $databaseName3];

            try {
                $parameters = new SetDatabasesLockParameters();
                $parameters->setDatabaseNames($databases);
                $parameters->setMode(DatabaseLockMode::preventDeletesError());
                $store->maintenance()->server()->send(new SetDatabasesLockOperation($parameters));
                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(DatabaseDoesNotExistException::class, $exception);
            }

            $store->maintenance()->server()->send(new CreateDatabaseOperation(new DatabaseRecord($databaseName1)));

            try {
                $parameters = new SetDatabasesLockParameters();
                $parameters->setDatabaseNames($databases);
                $parameters->setMode(DatabaseLockMode::preventDeletesError());
                $store->maintenance()->server()->send(new SetDatabasesLockOperation($parameters));
                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(DatabaseDoesNotExistException::class, $exception);
            }

            self::assertLockMode($store, $databaseName1, DatabaseLockMode::unlock());

            $store->maintenance()->server()->send(new CreateDatabaseOperation(new DatabaseRecord($databaseName2)));
            $store->maintenance()->server()->send(new CreateDatabaseOperation(new DatabaseRecord($databaseName3)));

            self::assertLockMode($store, $databaseName2, DatabaseLockMode::unlock());
            self::assertLockMode($store, $databaseName3, DatabaseLockMode::unlock());

            $p2 = new SetDatabasesLockParameters();
            $p2->setDatabaseNames($databases);
            $p2->setMode(DatabaseLockMode::preventDeletesError());
            $store->maintenance()->server()->send(new SetDatabasesLockOperation($p2));

            self::assertLockMode($store, $databaseName1, DatabaseLockMode::preventDeletesError());
            self::assertLockMode($store, $databaseName2, DatabaseLockMode::preventDeletesError());
            self::assertLockMode($store, $databaseName3, DatabaseLockMode::preventDeletesError());

            $store->maintenance()->server()->send(new SetDatabasesLockOperation($databaseName2, DatabaseLockMode::preventDeletesIgnore()));

            self::assertLockMode($store, $databaseName1, DatabaseLockMode::preventDeletesError());
            self::assertLockMode($store, $databaseName2, DatabaseLockMode::preventDeletesIgnore());
            self::assertLockMode($store, $databaseName3, DatabaseLockMode::preventDeletesError());

            $p2->setDatabaseNames($databases);
            $p2->setMode(DatabaseLockMode::preventDeletesIgnore());

            $store->maintenance()->server()->send(new SetDatabasesLockOperation($p2));

            self::assertLockMode($store, $databaseName1, DatabaseLockMode::preventDeletesIgnore());
            self::assertLockMode($store, $databaseName2, DatabaseLockMode::preventDeletesIgnore());
            self::assertLockMode($store, $databaseName3, DatabaseLockMode::preventDeletesIgnore());

            $p2->setDatabaseNames($databases);
            $p2->setMode(DatabaseLockMode::unlock());

            $store->maintenance()->server()->send(new SetDatabasesLockOperation($p2));

            $store->maintenance()->server()->send(new DeleteDatabasesOperation($databaseName1, true));
            $store->maintenance()->server()->send(new DeleteDatabasesOperation($databaseName2, true));
            $store->maintenance()->server()->send(new DeleteDatabasesOperation($databaseName3, true));
        } finally {
            $store->close();
        }
    }

    public function testCanLockDatabase_Disabled(): void
    {
        $store = $this->getDocumentStore();
        try {
            $databaseName = $store->getDatabase() . "_LockMode_1";

            $store->maintenance()->server()->send(new CreateDatabaseOperation(new DatabaseRecord($databaseName)));

            self::assertLockMode($store, $databaseName, DatabaseLockMode::unlock());

            $store->maintenance()->server()->send(new ToggleDatabasesStateOperation($databaseName, true));

            $store->maintenance()->server()->send(new SetDatabasesLockOperation($databaseName, DatabaseLockMode::preventDeletesError()));

            self::assertLockMode($store, $databaseName, DatabaseLockMode::preventDeletesError());

            $store->maintenance()->server()->send(new SetDatabasesLockOperation($databaseName, DatabaseLockMode::unlock()));

            $store->maintenance()->server()->send(new DeleteDatabasesOperation($databaseName, true));
        } finally {
            $store->close();
        }
    }

    private static function assertLockMode(?DocumentStoreInterface $store, ?string $databaseName, ?DatabaseLockMode $mode): void
    {
        /** @var DatabaseRecordWithEtag $databaseRecord */
        $databaseRecord = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($databaseName));
        self::assertEquals($mode, $databaseRecord->getLockMode());
    }
}
