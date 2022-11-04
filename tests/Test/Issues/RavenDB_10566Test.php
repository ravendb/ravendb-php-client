<?php

namespace tests\RavenDB\Test\Issues;

use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_10566Test extends RemoteTestBase
{
    public function testShouldBeAvailable(): void
    {
        $store = $this->getDocumentStore();
        try {
            $name = '';
            $store->addAfterSaveChangesListener(function($sender, $event) use (&$name) {
                $name =  $event->getDocumentMetadata()->get("Name");
            });

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");

                $session->store($user, "users/oren");
                $metadata = $session->advanced()->getMetadataFor($user);
                $metadata->put("Name", "FooBar");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->assertEquals("FooBar", $name);
        } finally {
            $store->close();
        }
    }
}
