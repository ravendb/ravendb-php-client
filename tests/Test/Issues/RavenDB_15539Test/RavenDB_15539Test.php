<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15539Test;

use RavenDB\Documents\DocumentStore;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15539Test extends RemoteTestBase
{
    protected function customizeStore(DocumentStore &$store): void
    {
        $store->getConventions()
                ->setShouldIgnoreEntityChanges(function($session, $entity, $id) {
                    return $entity instanceof User && $entity->isIgnoreChanges();
                });
    }

    public function testCanIgnoreChanges(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/oren");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/oren");
                $user->setName("Arava");
                $user->setIgnoreChanges(true);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/oren");
                $this->assertEquals("Oren", $user->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
