<?php

namespace tests\RavenDB\Test\Server\Documents;

use RavenDB\ServerWide\DatabaseRecord;
use RavenDB\ServerWide\DocumentsCompressionConfiguration;
use RavenDB\ServerWide\GetDatabaseRecordOperation;
use RavenDB\ServerWide\Operations\DocumentsCompression\UpdateDocumentsCompressionConfigurationOperation;
use tests\RavenDB\Infrastructure\DisableOnPullRequestCondition;
use tests\RavenDB\RemoteTestBase;

class CompressAllCollectionsTest extends RemoteTestBase
{
    public function setUp(): void
    {
        DisableOnPullRequestCondition::evaluateExecutionCondition($this);

        parent::setUp();
    }

    public function testCompressAllCollectionsAfterDocsChange(): void
    {
        $store = $this->getDocumentStore();
        try {
            // we are running in memory - just check if command will be send to server
            $store->maintenance()->send(new UpdateDocumentsCompressionConfigurationOperation(new DocumentsCompressionConfiguration(false, true)));

            /** @var DatabaseRecord  $record */
            $record = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($store->getDatabase()));

            $documentsCompression = $record->getDocumentsCompression();

            $this->assertTrue($documentsCompression->isCompressAllCollections());

            $this->assertFalse($documentsCompression->isCompressRevisions());
        } finally {
            $store->close();
        }
    }
}
