<?php

namespace tests\RavenDB\Test\Client;

use RavenDB\Type\StringList;
use tests\RavenDB\Infrastructure\Entity\GeekPerson;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class LoadTest extends RemoteTestBase
{
    public function testLoadCanUseCache(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");

                $session->store($user, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $newSession = $store->openSession();
            try {
                $user = $newSession->load(User::class, "users/1");
                $this->assertNotNull($user);
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                $user = $newSession->load(User::class, "users/1");
                $this->assertNotNull($user);
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testLoad_Document_And_Expect_Null_User(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $nullId = null;
                $user1 = $session->load(User::class, $nullId);
                $this->assertNull($user1);

                $user2 = $session->load(User::class, "");
                $this->assertNull($user2);

                $user3 = $session->load(User::class, " ");
                $this->assertNull($user3);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testLoadDocumentById(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");

                $session->store($user, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $newSession = $store->openSession();
            try {
                $user = $newSession->load(User::class, "users/1");
                $this->assertNotNull($user);

                $this->assertEquals("RavenDB", $user->getName());
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testLoadDocumentsByIds(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("RavenDB");

                $user2 = new User();
                $user2->setName("Hibernating Rhinos");

                $session->store($user1, "users/1");
                $session->store($user2, "users/2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $newSession = $store->openSession();
            try {
                $user = $newSession->load(User::class, "users/1", "users/2");
                $this->assertCount(2, $user);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testLoadNullShouldReturnNull(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Tony Montana");

                $user2 = new User();
                $user2->setName("Tony Soprano");

                $session->store($user1);
                $session->store($user2);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $newSession = $store->openSession();
            try {
                $user1 = $newSession->load(User::class, null);
                $this->assertNull($user1);
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testLoadMultiIdsWithNullShouldReturnDictionaryWithoutNulls(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Tony Montana");

                $user2 = new User();
                $user2->setName("Tony Soprano");

                $session->store($user1, "users/1");
                $session->store($user2, "users/2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $newSession = $store->openSession();
            try {
                $orderedArrayOfIdsWithNull = ["users/1", null, "users/2", null];

                $users1 = $newSession->load(User::class, $orderedArrayOfIdsWithNull);

                $this->assertArrayHasKey("users/1", $users1);
                $this->assertNotNull($users1["users/1"]);
                $this->assertArrayHasKey("users/2", $users1);
                $this->assertNotNull($users1["users/2"]);

                $unorderedSetOfIdsWithNull = $orderedArrayOfIdsWithNull;
                $users2 = $newSession->load(User::class, $unorderedSetOfIdsWithNull);

                $this->assertCount(2, $users2);
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testLoadDocumentWithINtArrayAndLongArray(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $geek1 = new GeekPerson();
                $geek1->setName("Bebop");
                $geek1->setFavoritePrimes([13, 43, 443, 997]);
                $geek1->setFavoriteVeryLargePrimes([5000000029, 5000000039]);

                $session->store($geek1, "geeks/1");

                $geek2 = new GeekPerson();
                $geek2->setName("Rocksteady");
                $geek2->setFavoritePrimes([2, 3, 5, 7]);
                $geek2->setFavoriteVeryLargePrimes([999999999989]);

                $session->store($geek2, "geeks/2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $newSession = $store->openSession();
            try {
                $geek1 = $newSession->load(GeekPerson::class, "geeks/1");
                $geek2 = $newSession->load(GeekPerson::class, "geeks/2");

                $this->assertEquals(43, $geek1->getFavoritePrimes()[1]);
                $this->assertEquals(5000000039, $geek1->getFavoriteVeryLargePrimes()[1]);

                $this->assertEquals(7, $geek2->getFavoritePrimes()[3]);
                $this->assertEquals(999999999989, $geek2->getFavoriteVeryLargePrimes()[0]);
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testShouldLoadManyIdsAsPostRequest(): void
    {
        $store = $this->getDocumentStore();
        try {
            $ids = new StringList();

            $session = $store->openSession();
            try {
                // Length of all the ids together should be larger than 1024 for POST request
                for ($i = 0; $i < 200; $i++) {
                    $id = "users/" . $i;
                    $ids->append($id);

                    $user = new User();
                    $user->setName("Person " . $i);
                    $session->store($user, $id);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $users = $session->load(User::class, $ids);

                $this->assertArrayHasKey("users/77", $users);

                $user77 = $users["users/77"];

                $this->assertNotNull($user77);
                $this->assertEquals("users/77", $user77->getId());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testLoadStartsWith(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $createUser = function($id) use ($session) {
                    $u = new User();
                    $u->setId($id);
                    $session->store($u);
                };

                $createUser("Aaa");
                $createUser("Abc");
                $createUser("Afa");
                $createUser("Ala");
                $createUser("Baa");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $newSession = $store->openSession();
            try {
                $users = $newSession->advanced()->loadStartingWith(User::class, "A");
                $usersIds = array_map(function($user) {
                    return $user->getId();
                }, $users->getArrayCopy());
                $expected = ["Aaa", "Abc", "Afa", "Ala"];
                $this->assertEquals($expected, $usersIds);

                $users = $newSession->advanced()->loadStartingWith(User::class, "A", null, 1, 2);
                $usersIds = array_map(function($user) {
                    return $user->getId();
                }, $users->getArrayCopy());
                $expected = ["Abc", "Afa"];
                $this->assertEquals($expected, $usersIds);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
