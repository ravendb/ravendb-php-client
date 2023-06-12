<?php

namespace tests\RavenDB\Test\Server;

use Exception;
use RavenDB\Documents\Indexes\AutoIndexDefinition;
use RavenDB\Documents\Indexes\AutoIndexFieldOptions;
use RavenDB\Documents\Operations\ToggleDatabasesStateOperation;
use RavenDB\ServerWide\DatabaseRecord;
use RavenDB\ServerWide\DatabaseRecordWithEtag;
use RavenDB\ServerWide\GetDatabaseRecordOperation;
use RavenDB\ServerWide\Operations\AddDatabaseNodeOperation;
use RavenDB\ServerWide\Operations\CreateDatabaseOperation;
use RavenDB\ServerWide\Operations\DeleteDatabasesOperation;
use tests\RavenDB\Infrastructure\Graph\Genre;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class DatabasesTest extends RemoteTestBase
{
    public function testCanDisableAndEnableDatabase(): void
    {
        $store = $this->getDocumentStore();
        try {
            $dbRecord = new DatabaseRecord("enableDisable");
            $databaseOperation = new CreateDatabaseOperation($dbRecord);
            $store->maintenance()->server()->send($databaseOperation);

            $toggleResult = $store->maintenance()->server()->send(
                    new ToggleDatabasesStateOperation("enableDisable", true));

            $this->assertNotNull($toggleResult);

            $this->assertNotNull($toggleResult->getName());

            $disabledDatabaseRecord = $store->maintenance()->server()->send(new GetDatabaseRecordOperation("enableDisable"));

            $this->assertTrue($disabledDatabaseRecord->isDisabled());

            // now enable database

            $toggleResult = $store->maintenance()->server()->send(
                    new ToggleDatabasesStateOperation("enableDisable", false));
            $this->assertNotNull($toggleResult);

            $this->assertNotNull($toggleResult->getName());

            $enabledDatabaseRecord = $store->maintenance()->server()->send(new GetDatabaseRecordOperation("enableDisable"));

            $this->assertFalse($enabledDatabaseRecord->isDisabled());
        } finally {
            $deleteDatabase = new DeleteDatabasesOperation("enableDisable");
            $store->maintenance()->server()->send($deleteDatabase);

            $store->close();
        }
    }

    public function testCanAddNode(): void
    {
        $store = $this->getDocumentStore();
        try {
            try {
                $send = $store->maintenance()->server()->send(new AddDatabaseNodeOperation($store->getDatabase()));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("Can't add node", $exception->getMessage());
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetInfoAutoIndexInfo(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->createMoviesData($store);

            $session = $store->openSession();
            try {
                $session->query(Genre::class)
                        ->whereEquals("name", "Fantasy")
                        ->toList();
            } finally {
                $session->close();
            }

            /** @var DatabaseRecordWithEtag $record */
            $record = $store->maintenance()->server()
                    ->send(new GetDatabaseRecordOperation($store->getDatabase()));

            $this->assertCount(1, $record->getAutoIndexes());

            $this->assertArrayHasKey("Auto/Genres/Byname", $record->getAutoIndexes());

            /** @var AutoIndexDefinition $autoIndexDefinition */
            $autoIndexDefinition = $record->getAutoIndexes()["Auto/Genres/Byname"];
            $this->assertNotNull($autoIndexDefinition);

            $this->assertTrue($autoIndexDefinition->getType()->isAutoMap());
            $this->assertEquals("Auto/Genres/Byname", $autoIndexDefinition->getName());
            $this->assertTrue($autoIndexDefinition->getPriority()->isNormal());
            $this->assertEquals("Genres", $autoIndexDefinition->getCollection());
            $this->assertCount(1, $autoIndexDefinition->getMapFields());
            $this->assertCount(0, $autoIndexDefinition->getGroupByFields());

            /** @var AutoIndexFieldOptions $fieldOptions */
            $fieldOptions = $autoIndexDefinition->getMapFields()["name"];
            $this->assertTrue($fieldOptions->getStorage()->isNo());
            $this->assertTrue($fieldOptions->getIndexing()->isDefault());
            $this->assertTrue($fieldOptions->getAggregation()->isNone());

            $this->assertNull($fieldOptions->getSpatial());
            $this->assertTrue($fieldOptions->getGroupByArrayBehavior()->isNotApplicable());

            $this->assertFalse($fieldOptions->getSuggestions());
            $this->assertFalse($fieldOptions->isNameQuoted());
        } finally {
            $store->close();
        }
    }
}
