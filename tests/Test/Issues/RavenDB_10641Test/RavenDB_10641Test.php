<?php

namespace tests\RavenDB\Test\Issues\RavenDB_10641Test;

use tests\RavenDB\RemoteTestBase;

class RavenDB_10641Test extends RemoteTestBase
{
    public function testCanEditObjectsInMetadata(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $v = new Document();
                $session->store($v, "items/first");

                $items = [];
                $items["lang"] = "en";

                $session->advanced()->getMetadataFor($v)
                        ->put("Items", $items);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $v = $session->load(Document::class, "items/first");
                $metadata = $session->advanced()->getMetadataFor($v)->get("Items");
                $metadata["lang"] = "sv";

                $session->advanced()->getMetadataFor($v)->put("Items", $metadata);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $v = $session->load(Document::class, "items/first");
                $metadata = $session->advanced()->getMetadataFor($v);
                $metadata->put("test", "123");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $v = $session->load(Document::class, "items/first");
                $metadata = $session->advanced()->getMetadataFor($v);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $v = $session->load(Document::class, "items/first");
                $metadata = $session->advanced()->getMetadataFor($v);
                $this->assertEquals("sv",$metadata->get("Items")["lang"]);
                $this->assertEquals("123",$metadata->get("test"));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
