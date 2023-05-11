<?php

namespace tests\RavenDB\Test\MailingList\_LoadAllStartingWithTest;

use tests\RavenDB\RemoteTestBase;

class LoadAllStartingWithTest extends RemoteTestBase
{
    public function testLoadAllStartingWith(): void
    {
        $store = $this->getDocumentStore();
        try {
            $doc1 = new Abc();
            $doc1->setId("abc/1");

            $doc2 = new Xyz();
            $doc2->setId("xyz/1");

            $session = $store->openSession();
            try {
                $session->store($doc1);
                $session->store($doc2);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $testClasses =
                        $session->advanced()->lazily()->loadStartingWith(Abc::class, "abc/");

                $test2Classes = $session->query(Xyz::class)->waitForNonStaleResults()
                        ->lazily()->getValue();

                $this->assertCount(1, $testClasses->getValue());

                $this->assertCount(1, $test2Classes);

                $this->assertEquals("abc/1", $testClasses->getValue()["abc/1"]->getId());

                $this->assertEquals("xyz/1", $test2Classes[0]->getId());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
