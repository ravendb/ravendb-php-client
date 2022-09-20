<?php

namespace tests\RavenDB\Test\Issues\RDBC_316Test;

use tests\RavenDB\RemoteTestBase;

class RDBC_316Test extends RemoteTestBase
{
    public function testCanStoreEqualDocumentUnderTwoDifferentKeys(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Marcin");

                $user2 = new User();
                $user2->setName("Marcin");

                $this->assertEquals($user1, $user2);

                $session->store($user1, "users/1");
                $session->store($user2, "users/2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user1 = $session->load(User::class, "users/1");
                $user2 = $session->load(User::class, "users/2");

                $this->assertNotNull($user1);
                $this->assertNotNull($user2);

                $this->assertNotSame($user1, $user2);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
