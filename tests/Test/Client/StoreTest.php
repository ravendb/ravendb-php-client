<?php

namespace tests\RavenDB\Test\Client;

use RavenDB\Documents\Session\MetadataDictionaryInterfaceArray;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class StoreTest extends RemoteTestBase
{
    public function testRefreshTest(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");
                $session->store($user, "users/1");
                $session->saveChanges();

                $innerSession = $store->openSession();
                try {
                    $innerUser = $innerSession->load(User::class, "users/1");
                    $innerUser->setName("RavenDB 4.0");
                    $innerSession->saveChanges();
                } finally {
                    $innerSession->close();
                }

                $session->advanced()->refresh($user);

                $this->assertEquals("RavenDB 4.0", $user->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testStoreDocument(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");
                $session->store($user, "users/1");
                $session->saveChanges();

                $user = $session->load(User::class, "users/1");
                $this->assertNotNull($user);
                $this->assertEquals("RavenDB", $user->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testStoreDocuments(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("RavenDB");
                $session->store($user1, "users/1");

                $user2 = new User();
                $user2->setName("Hibernating Rhinos");
                $session->store($user2, "users/2");

                $session->saveChanges();

                $users = $session->load(User::class, "users/1", "users/2");
                $this->assertCount(2, $users);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testNotifyAfterStore(): void
    {
        $store = $this->getDocumentStore();
        try {
            $storeLevelCallBack = new MetadataDictionaryInterfaceArray();
            $storeLevelCallBack->allowNull();
            $storeLevelCallBack->offsetSet(0, null);

            $sessionLevelCallback = new MetadataDictionaryInterfaceArray();
            $sessionLevelCallback->allowNull();
            $sessionLevelCallback->offsetSet(0, null);

            $store->addAfterSaveChangesListener(function ($sender, $event) use ($storeLevelCallBack) {
                $storeLevelCallBack[0] = $event->getDocumentMetadata();
            });

            $session = $store->openSession();
            try {
                $session->advanced()->addAfterSaveChangesListener(function ($sender, $event) use ($sessionLevelCallback) {
                    $sessionLevelCallback[0] = $event->getDocumentMetadata();
                });

                $user1 = new User();
                $user1->setName("RavenDB");
                $session->store($user1, "users/1");

                $session->saveChanges();

                $this->assertTrue($session->advanced()->isLoaded("users/1"));

                $this->assertNotNull($session->advanced()->getChangeVectorFor($user1));

                $this->assertNotNull($session->advanced()->getLastModifiedFor($user1));
            } finally {
                $session->close();
            }

            $this->assertNotNull($storeLevelCallBack[0]);
            $this->assertEquals($storeLevelCallBack[0], $sessionLevelCallback[0]);

            $this->assertNotNull($sessionLevelCallback[0]);

            $iMetadataDictionary = $sessionLevelCallback[0];
            foreach ($iMetadataDictionary as $key => $value) {
                $this->assertNotNull($key);
                $this->assertNotNull($value);
            }
        } finally {
            $store->close();
        }
    }
}
