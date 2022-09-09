<?php

namespace tests\RavenDB\Test\Client;

use RavenDB\Documents\Operations\Identities\GetIdentitiesOperation;
use RavenDB\Documents\Operations\Identities\NextIdentityForOperation;
use RavenDB\Documents\Operations\Identities\SeedIdentityForOperation;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

// !status: DONE
class NextAndSeedIdentitiesTest extends RemoteTestBase
{
    public function testNextIdentityFor(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setLastName("Adi");

                $session->store($user, "users|");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $store->maintenance()->send(new NextIdentityForOperation("users"));

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setLastName("Avivi");

                $session->store($user, "users|");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $identities = $store->maintenance()->send(new GetIdentitiesOperation());

            $this->assertArrayHasKey('users|', $identities);
            $this->assertEquals(3, $identities['users|']);

            $session = $store->openSession();
            try {
                $entityWithId1 = $session->load(User::class, "users/1");
                $entityWithId2 = $session->load(User::class, "users/2");
                $entityWithId3 = $session->load(User::class, "users/3");
                $entityWithId4 = $session->load(User::class, "users/4");

                $this->assertNotNull($entityWithId1);
                $this->assertNotNull($entityWithId3);
                $this->assertNull($entityWithId2);
                $this->assertNull($entityWithId4);

                $this->assertEquals("Adi", $entityWithId1->getLastName());
                $this->assertEquals("Avivi", $entityWithId3->getLastName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSeedIdentityFor(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setLastName("Adi");

                $session->store($user, "users|");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $result1 = $store->maintenance()->send(new SeedIdentityForOperation("users", 1990));
            $this->assertEquals(1990, $result1);

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setLastName("Avivi");
                $session->store($user, "users|");
                $session->saveChanges();
        } finally {
            $session->close();
        }

            $session = $store->openSession();
            try {
                $entityWithId1 = $session->load(User::class, "users/1");
                $entityWithId2 = $session->load(User::class, "users/2");
                $entityWithId1990 = $session->load(User::class, "users/1990");
                $entityWithId1991 = $session->load(User::class, "users/1991");
                $entityWithId1992 = $session->load(User::class, "users/1992");

                $this->assertNotNull($entityWithId1);
                $this->assertNotNull($entityWithId1991);

                $this->assertNull($entityWithId2);
                $this->assertNull($entityWithId1990);
                $this->assertNull($entityWithId1992);

                $this->assertEquals("Adi", $entityWithId1->getLastName());
                $this->assertEquals("Avivi", $entityWithId1991->getLastName());
        } finally {
            $session->close();
        }

            $result2 = $store->maintenance()->send(new SeedIdentityForOperation("users", 1975));
            $this->assertEquals(1991, $result2);

            $result3 = $store->maintenance()->send(new SeedIdentityForOperation("users", 1975, true));
            $this->assertEquals(1975, $result3);
        } finally {
            $store->close();
        }
    }

    public function testNextIdentityForOperationShouldCreateANewIdentityIfThereIsNone(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $result = $store->maintenance()->send(new NextIdentityForOperation("person|"));

                $this->assertEquals(1, $result);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
