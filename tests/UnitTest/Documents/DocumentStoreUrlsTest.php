<?php

namespace tests\RavenDB\UnitTest\Documents;

use PHPUnit\Framework\TestCase;
use RavenDB\Documents\DocumentStore;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Type\Url;
use RavenDB\Type\UrlArray;

class DocumentStoreUrlsTest extends TestCase
{
    public function testInitDocumentStoreWithoutUrls(): void
    {
        $store = new DocumentStore(null, 'db_name');

        $this->assertEquals('db_name', $store->getDatabase());
        $this->assertNull($store->getUrls());
    }

    public function testInitDocumentStoreWithUrlArray(): void
    {
        $urls = new UrlArray();
        $urls->append(new Url('https://db.location'));
        $store = new DocumentStore($urls);

        $this->assertNull($store->getDatabase());
        $this->assertCount(1, $store->getUrls());
        $this->assertEquals('https://db.location', $store->getUrls()->first());
    }

    public function testInitDocumentStoreWithSingleUrl(): void
    {
        $store = new DocumentStore(new Url('https://db.location'));

        $this->assertNull($store->getDatabase());
        $this->assertCount(1, $store->getUrls());
        $this->assertEquals('https://db.location', $store->getUrls()->first());
    }

    public function testInitDocumentStoreWithArrayWithStringUrl(): void
    {
        $store = new DocumentStore(['https://db.location']);

        $this->assertNull($store->getDatabase());
        $this->assertCount(1, $store->getUrls());
        $this->assertEquals('https://db.location', $store->getUrls()->first());
    }

    public function testInitDocumentStoreWithArrayWithStringUrls(): void
    {
        $store = new DocumentStore(['https://db.location', 'https://second.db.location']);

        $this->assertNull($store->getDatabase());
        $this->assertCount(2, $store->getUrls());
        $this->assertEquals('https://db.location', $store->getUrls()[0]);
        $this->assertEquals('https://second.db.location', $store->getUrls()[1]);
    }

    public function testInitDocumentStoreWithStringUrl(): void
    {
        $store = new DocumentStore('https://db.location');

        $this->assertNull($store->getDatabase());
        $this->assertCount(1, $store->getUrls());
        $this->assertEquals('https://db.location', $store->getUrls()->first());
    }

    public function testThrowsExceptionWhenTryToSetNullAsUrls(): void
    {
        $store = new DocumentStore();

        $this->expectException(IllegalArgumentException::class);
        $this->expectExceptionMessage('Url is null');

        $store->setUrls(null);
    }

    public function testThrowsExceptionWhenUrlsContainsNull(): void
    {
        $store = new DocumentStore();

        $this->expectException(IllegalArgumentException::class);
        $this->expectExceptionMessage('Urls cannot contain null');

        $store->setUrls(['https://db.location', null]);
    }

    public function testStripForwardSlashFromEndOfUrlString(): void
    {
        $store = new DocumentStore('https://db.location/');
        $this->assertEquals('https://db.location', $store->getUrls()->first());
    }

    public function testThrowsExceptionOnMallformedUrl(): void
    {
        $url = 'this is just a regular text';
        $this->expectException(IllegalArgumentException::class);
        $this->expectExceptionMessage(sprintf("The url '%s' is not valid", $url));

        $store = new DocumentStore($url);
    }
}
