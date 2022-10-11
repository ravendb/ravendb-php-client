<?php

namespace tests\RavenDB\Test\Server;

use RavenDB\Documents\Indexes\AutoIndexDefinition;
use RavenDB\Documents\Indexes\AutoIndexFieldOptions;
use RavenDB\ServerWide\DatabaseRecordWithEtag;
use RavenDB\ServerWide\GetDatabaseRecordOperation;
use tests\RavenDB\Infrastructure\Graph\Genre;
use tests\RavenDB\RemoteTestBase;

class DatabasesTest extends RemoteTestBase
{
//    public void canDisableAndEnableDatabase() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            DatabaseRecord dbRecord = new DatabaseRecord("enableDisable");
//            CreateDatabaseOperation databaseOperation = new CreateDatabaseOperation(dbRecord);
//            store.maintenance().server().send(databaseOperation);
//
//            DisableDatabaseToggleResult toggleResult = store.maintenance().server().send(
//                    new ToggleDatabasesStateOperation("enableDisable", true));
//
//            assertThat(toggleResult)
//                    .isNotNull();
//            assertThat(toggleResult.getName())
//                    .isNotNull();
//
//            DatabaseRecordWithEtag disabledDatabaseRecord = store.maintenance().server().send(new GetDatabaseRecordOperation("enableDisable"));
//
//            assertThat(disabledDatabaseRecord.isDisabled())
//                    .isTrue();
//
//            // now enable database
//
//            toggleResult = store.maintenance().server().send(
//                    new ToggleDatabasesStateOperation("enableDisable", false));
//            assertThat(toggleResult)
//                    .isNotNull();
//            assertThat(toggleResult.getName())
//                    .isNotNull();
//
//            DatabaseRecordWithEtag enabledDatabaseRecord = store.maintenance().server().send(new GetDatabaseRecordOperation("enableDisable"));
//
//            assertThat(enabledDatabaseRecord.isDisabled())
//                    .isFalse();
//        }
//    }
//
//    @Test
//    public void canAddNode() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            assertThatThrownBy(() -> {
//                // we assert this by throwing - we are running single node cluster
//                DatabasePutResult send = store.maintenance().server().send(new AddDatabaseNodeOperation(store.getDatabase()));
//            }).hasMessageContaining("Can't add node");
//        }
//    }
//

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
