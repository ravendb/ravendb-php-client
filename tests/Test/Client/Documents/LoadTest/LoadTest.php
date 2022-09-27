<?php

namespace tests\RavenDB\Test\Client\Documents\LoadTest;

use RavenDB\Type\StringArray;
use tests\RavenDB\RemoteTestBase;

class LoadTest extends RemoteTestBase
{
    public function testLoadWithIncludes(): void
    {
        $store = $this->getDocumentStore();
        try {
            $barId = '';

            $session = $store->openSession();
            try {
                $foo = new Foo();
                $foo->setName("Beginning");
                $session->store($foo);

                $fooId = $session->advanced()->getDocumentId($foo);
                $bar = new Bar();
                $bar->setName("End");
                $bar->setFooId($fooId);

                $session->store($bar);

                $barId = $session->advanced()->getDocumentId($bar);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $newSession = $store->openSession();
            try  {
                $bar = $newSession
                    ->include("fooId")
                    ->load(Bar::class, StringArray::fromArray([$barId]));

                $this->assertNotNull($bar);

                $this->assertCount(1, $bar);

                $this->assertNotNull($bar->offsetGet($barId));

                $numOfRequests = $newSession->advanced()->getNumberOfRequests();

                $foo = $newSession->load(Foo::class, $bar->offsetGet($barId)->getFooId());

                $this->assertNotNull($foo);

                $this->assertEquals("Beginning", $foo->getName());

                $this->assertEquals($numOfRequests, $newSession->advanced()->getNumberOfRequests());
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }


    public function testLoadWithIncludesAndMissingDocument(): void
    {
        $store = $this->getDocumentStore();
        try {
            $barId = '';

            $session = $store->openSession();
            try {
                $bar = new Bar();
                $bar->setName("End");
                $bar->setFooId("somefoo/1");

                $session->store($bar);
                $barId = $session->advanced()->getDocumentId($bar);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $newSession = $store->openSession();
            try {
                $bar = $newSession
                        ->include("fooId")
                        ->load(Bar::class, StringArray::fromArray([$barId]));

                $this->assertNotNull($bar);
                $this->assertCount(1, $bar);

                $this->assertNotNull($bar->offsetGet($barId));

                $numOfRequests = $newSession->advanced()->getNumberOfRequests();

                $foo = $newSession->load(Foo::class, $bar->offsetGet($barId)->getFooId());

                $this->assertNull($foo);

                // @todo: uncomment this
//                $this->assertEquals($numOfRequests, $newSession->advanced()->getNumberOfRequests());
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }
}
