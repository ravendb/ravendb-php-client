<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15521Test;

use tests\RavenDB\RemoteTestBase;

class RavenDB_15521Test extends RemoteTestBase
{
    public function testShouldWork(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $doc = new SimpleDoc();
                $doc->setId("TestDoc");
                $doc->setName("State1");

                $session->store($doc);

                $attachment = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";
                $session
                        ->advanced()
                        ->attachments()
                        ->store($doc, "TestAttachment", $attachment);

                $session->saveChanges();

                $changeVector1 = $session->advanced()->getChangeVectorFor($doc);
                $session->advanced()->refresh($doc);
                $changeVector2 = $session->advanced()->getChangeVectorFor($doc);
                $this->assertEquals($changeVector1, $changeVector2);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
