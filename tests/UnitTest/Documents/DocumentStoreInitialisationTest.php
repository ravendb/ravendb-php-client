<?php

namespace tests\RavenDB\UnitTest\Documents;

use PHPUnit\Framework\TestCase;
use RavenDB\Documents\DocumentStore;
use RavenDB\Type\Url;
use RavenDB\Type\UrlArray;

class DocumentStoreInitialisationTest extends TestCase
{
    public function testInitDocumentStoreWithoutParameters(): void
    {
        $store = new DocumentStore();

        $this->assertNull($store->getDatabase());
        $this->assertNull($store->getUrls());
    }

    public function testInitDocumentStoreHappyPath(): void
    {
        $store = new DocumentStore('https://db.location', 'db_name');

        $this->assertEquals('db_name', $store->getDatabase());
        $this->assertCount(1, $store->getUrls());
        $this->assertEquals('https://db.location', $store->getUrls()->first());
    }

    public function testInitDocumentStoreWithDBName(): void
    {
        $store = new DocumentStore(null, 'db_name');

        $this->assertEquals('db_name', $store->getDatabase());
        $this->assertNull($store->getUrls());
    }
}
