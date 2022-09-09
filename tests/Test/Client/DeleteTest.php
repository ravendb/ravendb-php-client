<?php

namespace tests\RavenDB\Test\Client;

use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class DeleteTest extends RemoteTestBase
{
    public function testDeleteDocumentByEntity(): void
    {
        $store = $this->getDocumentStore();
        try {
            $newSession = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");
                $newSession->store($user, "users/1");
                $newSession->saveChanges();

                $user = $newSession->load(User::class, "users/1");

                $this->assertNotNull($user);

                $newSession->delete($user);
                $newSession->saveChanges();

                $nullUser = $newSession->load(User::class, "users/1");
                $this->assertNull($nullUser);
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testDeleteDocumentById(): void
    {
        $store = $this->getDocumentStore();
        try {

            $newSession = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");
                $newSession->store($user, "users/1");
                $newSession->saveChanges();

                $user = $newSession->load(User::class, "users/1");

                $this->assertNotNull($user);

                $newSession->delete("users/1");
                $newSession->saveChanges();

                $nullUser = $newSession->load(User::class, "users/1");
                $this->assertNull($nullUser);
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }
}
