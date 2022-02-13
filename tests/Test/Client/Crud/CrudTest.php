<?php

namespace tests\RavenDB\Test\Client\Crud;

use RavenDB\Documents\Commands\GetDocumentsCommand;
use RavenDB\Documents\Commands\GetDocumentsResult;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\Crud\Entities\Arr1;
use tests\RavenDB\Test\Client\Crud\Entities\Arr2;
use tests\RavenDB\Test\Client\Crud\Entities\Family;
use tests\RavenDB\Test\Client\Crud\Entities\FamilyMembers;
use tests\RavenDB\Test\Client\Crud\Entities\Member;
use tests\RavenDB\Test\Client\Crud\Entities\MemberArray;
use tests\RavenDB\Test\Client\Crud\Entities\Poc;

class CrudTest extends RemoteTestBase
{

    public function testEntitiesAreSavedUsingLowerCase(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();
            try {
                $user1 = new User();
                $user1->setLastName("user1");
                $newSession->store($user1, "users/1");
                $newSession->saveChanges();

            } finally {
                $newSession->close();
            }

            $documentsCommand = GetDocumentsCommand::forSingleDocument("users/1");
            $store->getRequestExecutor()->execute($documentsCommand);

            /** @var GetDocumentsResult $result */
            $result = $documentsCommand->getResult();

            $userJson = $result->getResults()[0];
            $this->assertTrue(array_key_exists("lastName", $userJson));

            $newSession = $store->openSession();
            try {
//                @todo: Uncommnet this when we implement rawQueries
//                $users = $newSession->advanced()->rawQuery(User::class, "from Users where lastName = 'user1'");
//                $this->assertCount(1, $users);
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }


    public function testCanCustomizePropertyNamingStrategy(): void
    {
        $store = $this->getDocumentStore();

        try {
            $this->assertTrue(true);
//            store.getConventions().getEntityMapper().setPropertyNamingStrategy(new JsonExtensions.DotNetNamingStrategy());
//
//            try (IDocumentSession newSession = store.openSession()) {
//                User user1 = new User();
//                user1.setLastName("user1");
//                newSession.store(user1, "users/1");
//                newSession.saveChanges();
//            }
//
//            GetDocumentsCommand documentsCommand = new GetDocumentsCommand("users/1", null, false);
//            store.getRequestExecutor().execute(documentsCommand);
//
//            GetDocumentsResult result = documentsCommand.getResult();
//
//            JsonNode userJson = result.getResults().get(0);
//            assertThat(userJson.has("LastName"))
//                    .isTrue();
//
//            try (IDocumentSession newSession = store.openSession()) {
//                List<User> users = newSession.advanced().rawQuery(User.class, "from Users where LastName = 'user1'").toList();
//
//                assertThat(users)
//                        .hasSize(1);
//            }
        } finally {
            $store->close();
        }
    }

    // @todo: implement DELETE in order this test to work, and then uncomment all lines
    public function testCrudOperations(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();
            try {
                $user1 = new User();
                $user1->setLastName("user1");
                $newSession->store($user1, "users/1");

                $user2 = new User();
                $user2->setName("user2");
                $user1->setAge(1);
                $newSession->store($user2, "users/2");

                $user3 = new User();
                $user3->setName("user3");
                $user3->setAge(1);
                $newSession->store($user3, "users/3");


                $user4 = new User();
                $user4->setName("user4");
                $newSession->store($user4, "users/4");

//                $newSession->delete($user2);
                $user3->setAge(3);
                $newSession->saveChanges();

                $tempUser = $newSession->load(User::class, "users/2");
//                $this->assertNull($tempUser);

                /** @var User $tempUser */
                $tempUser = $newSession->load(User::class, "users/3");
                $this->assertEquals(3, $tempUser->getAge());

                $user1 = $newSession->load(User::class, "users/1");
                $user4 = $newSession->load(User::class, "users/4");

//                $newSession->delete($user4);
                $user1->setAge(10);
                $newSession->saveChanges();

                $tempUser = $newSession->load(User::class, "users/4");
//                $this->assertNull($tempUser);
                /** @var User $tempUser */
                $tempUser = $newSession->load(User::class, "users/1");
                $this->assertEquals(10, $tempUser->getAge());

            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    // @todo: implement DELETE in order this test to work, and then uncomment all lines
    public function testCrudOperationsWithWhatChanged(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();
            try {
                $user1 = new User();
                $user1->setLastName("user1");
                $newSession->store($user1, "users/1");

                $user2 = new User();
                $user2->setName("user2");
                $user1->setAge(1);
                $newSession->store($user2, "users/2");

                $user3 = new User();
                $user3->setName("user3");
                $user3->setAge(1);
                $newSession->store($user3, "users/3");

                $user4 = new User();
                $user4->setName("user4");
                $newSession->store($user4, "users/4");

//                $newSession->delete($user2);
                $user3->setAge(3);

                $this->assertCount(4, $newSession->advanced()->whatChanged());

                $newSession->saveChanges();

                $tempUser = $newSession->load(User::class, "users/2");
//                $this->assertNull($tempUser);

                /** @var User $tempUser */
                $tempUser = $newSession->load(User::class, "users/3");
                $this->assertEquals(3, $tempUser->getAge());

                $user1 = $newSession->load(User::class, "users/1");
                $user4 = $newSession->load(User::class, "users/4");

//                $newSession->delete($user4);
                $user1->setAge(10);
//                $this->assertCount(2, $newSession->advanced()->whatChanged());


                $newSession->saveChanges();

                $tempUser = $newSession->load(User::class, "users/4");
//                $this->assertNull($tempUser);

                /** @var User $tempUser */
                $tempUser = $newSession->load(User::class, "users/1");
                $this->assertEquals(10, $tempUser->getAge());

            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCrudOperationsWithArrayInObject(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();

            try {
                $names = [
                    "Hibernating Rhinos",
                    "RavenDB",
                ];

                $family = new Family();
                $family->setNames($names);

                $newSession->store($family, 'family/1');
                $newSession->saveChanges();

                /** @var Family $newFamily */
                $newFamily = $newSession->load(Family::class, 'family/1');

                $newFamily->setNames([
                    "Toli",
                    "Mitzi",
                    "Boki",
                ]);

                $this->assertEquals(1, count($newSession->advanced()->whatChanged()));

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCrudOperationsWithArrayInObject2(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();

            try {
                $names = [
                    "Hibernating Rhinos",
                    "RavenDB",
                ];

                $family = new Family();
                $family->setNames($names);

                $newSession->store($family, 'family/1');
                $newSession->saveChanges();

                /** @var Family $newFamily */
                $newFamily = $newSession->load(Family::class, 'family/1');

                $names1 = [
                    "Hibernating Rhinos",
                    "RavenDB",
                ];
                $newFamily->setNames($names1);

                $this->assertCount(0, $newSession->advanced()->whatChanged());

                $names2 = [
                    "RavenDB",
                    "Hibernating Rhinos",
                ];
                $newFamily->setNames($names2);

                $this->assertCount(1, $newSession->advanced()->whatChanged());

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCrudOperationsWithArrayInObject3(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();

            try {
                $names = [
                    "Hibernating Rhinos",
                    "RavenDB",
                ];

                $family = new Family();
                $family->setNames($names);

                $newSession->store($family, 'family/1');
                $newSession->saveChanges();

                /** @var Family $newFamily */
                $newFamily = $newSession->load(Family::class, 'family/1');

                $newFamily->setNames(['RavenDB']);

                $this->assertCount(1, $newSession->advanced()->whatChanged());

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCrudOperationsWithArrayInObject4(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();

            try {
                $names = [
                    "Hibernating Rhinos",
                    "RavenDB",
                ];

                $family = new Family();
                $family->setNames($names);

                $newSession->store($family, 'family/1');
                $newSession->saveChanges();

                /** @var Family $newFamily */
                $newFamily = $newSession->load(Family::class, 'family/1');

                $newFamily->setNames([
                    "RavenDB",
                    "Hibernating Rhinos",
                    "Toli",
                    "Mitzi",
                    "Boki"
                ]);

                $this->assertCount(1, $newSession->advanced()->whatChanged());

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCrudOperationsWithNull(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();

            try {
                $user = new User();
                $user->setName(null);

                $newSession->store($user, "users/1");
                $newSession->saveChanges();

                /** @var User $user2 */
                $user2 = $newSession->load(User::class, "users/1");
                $this->assertEmpty($newSession->advanced()->whatChanged());

                $user2->setAge(3);
                $this->assertCount(1, $newSession->advanced()->whatChanged());

            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCrudOperationsWithArrayOfObjects(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();

            try {
                $member1 = new Member();
                $member1->setName("Hibernating Rhinos");
                $member1->setAge(8);

                $member2 = new Member();
                $member2->setName("RavenDB");
                $member2->setAge(4);

                $members = new MemberArray();
                $members->append($member1);
                $members->append($member2);

                $family = new FamilyMembers();
                $family->setMembers($members);

                $newSession->store($family, "family/1");
                $newSession->saveChanges();

                $member1 = new Member();
                $member1->setName("RavenDB");
                $member1->setAge(4);

                $member2 = new Member();
                $member2->setName("Hibernating Rhinos");
                $member2->setAge(8);

                /** @var FamilyMembers $newFamily */
                $newFamily = $newSession->load(FamilyMembers::class, 'family/1');
                $members = new MemberArray();
                $members->append($member1);
                $members->append($member2);
                $newFamily->setMembers($members);

                /** @var array<string, array> $changes */
                $changes = $newSession->advanced()->whatChanged();

                $this->assertCount(1, $changes);

                $this->assertCount(4, $changes["family/1"]);

                $this->assertSame("name", $changes["family/1"][0]->getFieldName());


                $this->assertTrue($changes["family/1"][0]->getChange()->isFieldChanged());

                // @todo: Check with Marcin: Do we need quotes in string or not?
//                $this->assertSame(
//                    "\"Hibernating Rhinos\"",
//                    $changes["family/1"][0]->getFieldOldValue()
//                );
//                $this->assertSame(
//                    "\"RavenDB\"",
//                    $changes["family/1"][0]->getFieldNewValue()
//                );
                $this->assertSame(
                    "Hibernating Rhinos",
                    strval($changes["family/1"][0]->getFieldOldValue())
                );
                $this->assertSame(
                    "RavenDB",
                    strval($changes["family/1"][0]->getFieldNewValue())
                );

                $this->assertSame("age", $changes["family/1"][1]->getFieldName());
                $this->assertTrue($changes["family/1"][1]->getChange()->isFieldChanged());
                $this->assertSame(
                    "8",
                    strval($changes["family/1"][1]->getFieldOldValue())
                );
                $this->assertSame(
                    "4",
                    strval($changes["family/1"][1]->getFieldNewValue())
                );

                $this->assertSame("name", $changes["family/1"][2]->getFieldName());
                $this->assertTrue($changes["family/1"][2]->getChange()->isFieldChanged());
                // @todo: Check with Marcin: do we need quotes in string?
                $this->assertSame(
                    "RavenDB",
                    strval($changes["family/1"][2]->getFieldOldValue())
                );
                $this->assertSame(
                    "Hibernating Rhinos",
                    strval($changes["family/1"][2]->getFieldNewValue())
                );

                $this->assertSame("age", $changes["family/1"][3]->getFieldName());
                $this->assertTrue($changes["family/1"][3]->getChange()->isFieldChanged());
                $this->assertSame(
                    "4",
                    strval($changes["family/1"][3]->getFieldOldValue())
                );
                $this->assertSame(
                    "8",
                    strval($changes["family/1"][3]->getFieldNewValue())
                );

                $member1 = new Member();
                $member1->setName("Toli");
                $member1->setAge(5);

                $member2 = new Member();
                $member2->setName("Boki");
                $member2->setAge(15);

                $members = new MemberArray();
                $members->append($member1);
                $members->append($member2);
                $newFamily->setMembers($members);

                /** @var array<string, array> $changes */
                $changes = $newSession->advanced()->whatChanged();

                $this->assertCount(1, $changes);

                $this->assertCount(4, $changes["family/1"]);

                $this->assertSame("name", $changes["family/1"][0]->getFieldName());
                $this->assertTrue($changes["family/1"][0]->getChange()->isFieldChanged());
                // @todo: Check with Marcin: do we need quotes in string?
                $this->assertSame(
                    "Hibernating Rhinos",
                    strval($changes["family/1"][0]->getFieldOldValue())
                );
                $this->assertSame(
                    "Toli",
                    strval($changes["family/1"][0]->getFieldNewValue())
                );

                $this->assertSame("age", $changes["family/1"][1]->getFieldName());
                $this->assertTrue($changes["family/1"][1]->getChange()->isFieldChanged());
                $this->assertSame(
                    "8",
                    strval($changes["family/1"][1]->getFieldOldValue())
                );
                $this->assertSame(
                    "5",
                    strval($changes["family/1"][1]->getFieldNewValue())
                );

                $this->assertSame("name", $changes["family/1"][2]->getFieldName());
                $this->assertTrue($changes["family/1"][2]->getChange()->isFieldChanged());
                // @todo: Check with Marcin: do we need quotes in string?
                $this->assertSame(
                    "RavenDB",
                    strval($changes["family/1"][2]->getFieldOldValue())
                );
                $this->assertSame(
                    "Boki",
                    strval($changes["family/1"][2]->getFieldNewValue())
                );

                $this->assertSame("age", $changes["family/1"][3]->getFieldName());
                $this->assertTrue($changes["family/1"][3]->getChange()->isFieldChanged());
                $this->assertSame(
                    "4",
                    strval($changes["family/1"][3]->getFieldOldValue())
                );
                $this->assertSame(
                    "15",
                    strval($changes["family/1"][3]->getFieldNewValue())
                );
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCrudOperationsWithArrayOfArrays(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();

            try {
                $a1 = new Arr1();
                $a1->setStr(["a", "b"]);

                $a2 = new Arr1();
                $a2->setStr(["c", "d"]);

                $arr = new Arr2();
                $arr->setArr1([$a1, $a2]);

                $newSession->store($arr, "arr/1");
                $newSession->saveChanges();

                $newArr = $newSession->load(Arr2::class, "arr/1");

                $a1 = new Arr1();
                $a1->setStr(["d", "c"]);

                $a2 = new Arr1();
                $a2->setStr(["a", "b"]);

                $newArr->setArr1([$a1, $a2]);

                $whatChanged = $newSession->advanced()->whatChanged();

                $this->assertCount(1, $whatChanged);

                $change = $whatChanged["arr/1"];
                $this->assertCount(4, $change);

                // @todo: Check with Marcin: do we need a quotes?
                $this->assertSame("a", strval($change[0]->getFieldOldValue()));
                $this->assertSame("d", strval($change[0]->getFieldNewValue()));

                $this->assertSame("b", strval($change[1]->getFieldOldValue()));
                $this->assertSame("c", strval($change[1]->getFieldNewValue()));

                $this->assertSame("c", strval($change[2]->getFieldOldValue()));
                $this->assertSame("a", strval($change[2]->getFieldNewValue()));

                $this->assertSame("d", strval($change[3]->getFieldOldValue()));
                $this->assertSame("b", strval($change[3]->getFieldNewValue()));

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                $newArr = $newSession->load(Arr2::class, "arr/1");
                $a1 = new Arr1();
                $a1->setStr(["q", "w"]);

                $a2 = new Arr1();
                $a2->setStr(["a", "b"]);

                $newArr->setArr1([$a1, $a2]);

                $whatChanged = $newSession->advanced()->whatChanged();

                $this->assertCount(1, $whatChanged);

                $change = $whatChanged["arr/1"];
                $this->assertCount(2, $change);

                $this->assertSame("d", strval($change[0]->getFieldOldValue()));
                $this->assertSame("q", strval($change[0]->getFieldNewValue()));

                $this->assertSame("c", strval($change[1]->getFieldOldValue()));
                $this->assertSame("w", strval($change[1]->getFieldNewValue()));

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCrudCanUpdatePropertyToNull(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();
            try {
                $user = new User();
                $user->setName("user1");

                $newSession->store($user, "users/1");
                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                /** @var User $user */
                $user = $newSession->load(User::class, 'users/1');
                $user->setName(null);

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                $user = $newSession->load(User::class, 'users/1');

                $this->assertNull($user->getName());
            } finally {
                $newSession->close();
            }

        } finally {
            $store->close();
        }
    }

    public function testCrudCanUpdatePropertyFromNullToObject(): void
    {
        $store = $this->getDocumentStore();

        try {
            $session = $store->openSession();
            try {
                $poc = new Poc();
                $poc->setName("aviv");
                $poc->setObj(null);

                $session->store($poc, "pocs/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var Poc $poc */
                $poc = $session->load(Poc::class, "pocs/1");
                $this->assertNull($poc->getObj());

                $user = new User();
                $poc->setObj($user);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var Poc $poc */
                $poc = $session->load(Poc::class, "pocs/1");
                $this->assertNotNull($poc->getObj());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
