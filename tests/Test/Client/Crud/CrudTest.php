<?php

namespace tests\RavenDB\Test\Client\Crud;

use InvalidArgumentException;
use RavenDB\Documents\Session\ChangeType;
use RavenDB\Exceptions\IllegalStateException;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\Crud\Entities\Arr1;
use tests\RavenDB\Test\Client\Crud\Entities\Arr2;
use tests\RavenDB\Test\Client\Crud\Entities\Family;
use tests\RavenDB\Test\Client\Crud\Entities\FamilyMembers;
use tests\RavenDB\Test\Client\Crud\Entities\Member;
use tests\RavenDB\Test\Client\Crud\Entities\MemberArray;

class CrudTest extends RemoteTestBase
{
    /**
     * @throws InvalidArgumentException
     * @throws IllegalStateException
     */
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
//                $newSession->saveChanges();

                $this->assertEquals(0, count($newSession->advanced()->whatChanged()));

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
//            $this->cleanUp($store);
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws IllegalStateException
     */
    public function AtestCrudOperationsWithArrayInObject2(): void
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

    /**
     * @throws InvalidArgumentException
     * @throws IllegalStateException
     */
    public function AtestCrudOperationsWithArrayInObject3(): void
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

                unset($names[0]);
                $newFamily->setNames($names);

                $this->assertCount(1, $newSession->advanced()->whatChanged());

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws IllegalStateException
     */
    public function AtestCrudOperationsWithArrayInObject4(): void
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

    public function AtestCrudOperationsWithArrayOfObjects(): void
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

                $family = new FamilyMembers();
                $members = new MemberArray();
                $members->append($member1);
                $members->append($member2);
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
                $newFamily = $newSession->load(Family::class, 'family/1');
                $members = new MemberArray();
                $members->append($member1);
                $members->append($member2);
                $newFamily->setMembers($members);

                /** @var array<string, array> $changes */
                $changes = $newSession->advanced()->whatChanged();

                $this->expectNotToPerformAssertions();
//                $this->assertCount(1, $changes);
//
//                $this->assertCount(4, $changes["family/1"]);
//
//                $this->assertSame("name", $changes["family/1"][0]->getFieldName());
//                $this->assertSame(ChangeType::fieldChanged(), $changes["family/1"][0]->getChange());
//                $this->assertSame(
//                    "\"Hibernating Rhinos\"",
//                    $changes["family/1"][0]->getFieldOldValue()->__toString()
//                );
//                $this->assertSame(
//                    "\"RavenDB\"",
//                    $changes["family/1"][0]->getFieldNewValue()->__toString()
//                );
//
//                $this->assertSame("age", $changes["family/1"][1]->getFieldName());
//                $this->assertSame(ChangeType::fieldChanged(), $changes["family/1"][1]->getChange());
//                $this->assertSame(
//                    "8",
//                    $changes["family/1"][1]->getFieldOldValue()->__toString()
//                );
//                $this->assertSame(
//                    "4",
//                    $changes["family/1"][1]->getFieldNewValue()->__toString()
//                );
//
//                $this->assertSame("name", $changes["family/1"][2]->getFieldName());
//                $this->assertSame(ChangeType::fieldChanged(), $changes["family/1"][2]->getChange());
//                $this->assertSame(
//                    "\"RavenDB\"",
//                    $changes["family/1"][2]->getFieldOldValue()->__toString()
//                );
//                $this->assertSame(
//                    "\"Hibernating Rhinos\"",
//                    $changes["family/1"][2]->getFieldNewValue()->__toString()
//                );
//
//                $this->assertSame("age", $changes["family/1"][3]->getFieldName());
//                $this->assertSame(ChangeType::fieldChanged(), $changes["family/1"][3]->getChange());
//                $this->assertSame(
//                    "4",
//                    $changes["family/1"][3]->getFieldOldValue()->__toString()
//                );
//                $this->assertSame(
//                    "8",
//                    $changes["family/1"][3]->getFieldNewValue()->__toString()
//                );

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

//                $this->assertCount(1, $changes);
//
//                $this->assertCount(4, $changes["family/1"]);
//
//                $this->assertSame("name", $changes["family/1"][0]->getFieldName());
//                $this->assertSame(ChangeType::fieldChanged(), $changes["family/1"][0]->getChange());
//                $this->assertSame(
//                    "\"Hibernating Rhinos\"",
//                    $changes["family/1"][0]->getFieldOldValue()->__toString()
//                );
//                $this->assertSame(
//                    "\"Toli\"",
//                    $changes["family/1"][0]->getFieldNewValue()->__toString()
//                );
//
//                $this->assertSame("age", $changes["family/1"][1]->getFieldName());
//                $this->assertSame(ChangeType::fieldChanged(), $changes["family/1"][1]->getChange());
//                $this->assertSame(
//                    "8",
//                    $changes["family/1"][1]->getFieldOldValue()->__toString()
//                );
//                $this->assertSame(
//                    "5",
//                    $changes["family/1"][1]->getFieldNewValue()->__toString()
//                );
//
//                $this->assertSame("name", $changes["family/1"][2]->getFieldName());
//                $this->assertSame(ChangeType::fieldChanged(), $changes["family/1"][2]->getChange());
//                $this->assertSame(
//                    "\"RavenDB\"",
//                    $changes["family/1"][2]->getFieldOldValue()->__toString()
//                );
//                $this->assertSame(
//                    "\"Boki\"",
//                    $changes["family/1"][2]->getFieldNewValue()->__toString()
//                );
//
//                $this->assertSame("age", $changes["family/1"][3]->getFieldName());
//                $this->assertSame(ChangeType::fieldChanged(), $changes["family/1"][3]->getChange());
//                $this->assertSame(
//                    "4",
//                    $changes["family/1"][3]->getFieldOldValue()->__toString()
//                );
//                $this->assertSame(
//                    "15",
//                    $changes["family/1"][3]->getFieldNewValue()->__toString()
//                );
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function AtestCrudOperationsWithArrayOfArrays(): void
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();

            try {
                $a1 = new Arr1();
                $a1->setStr(array("a", "b"));

                $a2 = new Arr1();
                $a2->setStr(array("c", "d"));

                $arr = new Arr2();
                $arr->setArr1(array($a1, $a2));

                $newSession->store($arr, "arr/1");
                $newSession->saveChanges();

                $newArr = $newSession->load(Arr2::class, "arr/1");

                $a1 = new Arr1();
                $a1->setStr(array("d", "c"));

                $a2 = new Arr1();
                $a1->setStr(array("a", "b"));

                $newArr->setArr1(array($a1, $a2));

//                /** @var array<string, array> $whatChanged */
//                $whatChanged = $newSession->advanced()->whatChanged();

//                $this->assertCount(1, $whatChanged);
//
//                $change = $whatChanged["arr/1"];
//                $this->assertCount(4, $change);
//
//                $this->assertSame("\"a\"", $change[0]->getFieldOldValue()->__toString());
//                $this->assertSame("\"d\"", $change[0]->getFieldNewValue()->__toString());
//
//                $this->assertSame("\"b\"", $change[1]->getFieldOldValue()->__toString());
//                $this->assertSame("\"c\"", $change[1]->getFieldNewValue()->__toString());
//
//                $this->assertSame("\"c\"", $change[2]->getFieldOldValue()->__toString());
//                $this->assertSame("\"a\"", $change[2]->getFieldNewValue()->__toString());
//
//                $this->assertSame("\"d\"", $change[3]->getFieldOldValue()->__toString());
//                $this->assertSame("\"b\"", $change[3]->getFieldNewValue()->__toString());

 //               $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }

        try {
            $newSession = $store->openSession();

            try {
                $newArr = $newSession->load(Arr2::class, "arr/1");
                $a1 = new Arr1();
                $a1->setStr(array("q", "w"));

                $a2 = new Arr1();
                $a2->setStr(array( "a", "b" ));

                $newArr->setArr1(array($a1, $a2));

//                /** @var array<string, array> $whatChanged */
//                $whatChanged = $newSession->advanced()->whatChanged();

//                $this->assertCount(1, $whatChanged);
//
//                $change = $whatChanged["arr/1"];
//                $this->assertCount(2, $change);
//
//                $this->assertSame("\"d\"", $change[0]->getFieldOldValue()->__toString());
//                $this->assertSame("\"q\"", $change[0]->getFieldNewValue()->__toString());
//
//                $this->assertSame("\"c\"", $change[1]->getFieldOldValue()->__toString());
//                $this->assertSame("\"w\"", $change[1]->getFieldNewValue()->__toString());

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws IllegalStateException
     */
    public function AtestCrudCanUpdatePropertyToNull(): void
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
        } finally {
            $store->close();
        }

        try {
            $newSession = $store->openSession();

            try {
                /** @var User $user */
                $user = $newSession->load(User::class, 'users/1');
                $user->setName(null);

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }

        try {
            $newSession = $store->openSession();

            try {
                $user = $newSession->load(User::class, 'users/1');

                $this->assertEquals(null, $user->getName());
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }
}
