<?php

namespace tests\RavenDB\Test\Client\_WhatChangedTest;

use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class WhatChangedTest extends RemoteTestBase
{
    public function testWhatChangedNewField(): void
    {
        $store = $this->getDocumentStore();
        try {

            $newSession = $store->openSession();
            try {
                $basicName = new BasicName();
                $basicName->setName("Toli");
                $newSession->store($basicName, "users/1");

                $this->assertCount(1, $newSession->advanced()->whatChanged());

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                $user = $newSession->load(NameAndAge::class, "users/1");
                $user->setAge(5);
                $changes = $newSession->advanced()->whatChanged();
                $this->assertCount(1, $changes['users/1']);

                $this->assertTrue($changes['users/1']->offsetGet(0)->getChange()->isNewField());

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testWhatChangedRemovedField(): void
    {
        $store = $this->getDocumentStore();
        try {

            $newSession = $store->openSession();
            try {
                $nameAndAge = new NameAndAge();
                $nameAndAge->setAge(5);
                $nameAndAge->setName("Toli");

                $newSession->store($nameAndAge, "users/1");

                $this->assertCount(1, $newSession->advanced()->whatChanged());

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                $newSession->load(BasicAge::class, "users/1");

                $changes = $newSession->advanced()->whatChanged();

                $this->assertCount(1, $changes['users/1']);

                $this->assertTrue($changes['users/1']->offsetGet(0)->getChange()->isRemovedField());

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testWhatChangedChangeField(): void
    {
        $store = $this->getDocumentStore();
        try {

            $newSession = $store->openSession();
            try {
                $basicAge = new BasicAge();
                $basicAge->setAge(5);
                $newSession->store($basicAge, "users/1");

                $this->assertCount(1, $newSession->advanced()->whatChanged());
                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                $newSession->load(IntNumber::class, "users/1");

                $changes = $newSession->advanced()->whatChanged();
                $this->assertCount(2, $changes['users/1']);

                $this->assertTrue($changes['users/1']->offsetGet(0)->getChange()->isRemovedField());
                $this->assertTrue($changes['users/1']->offsetGet(1)->getChange()->isNewField());

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testWhatChangedArrayValueChanged(): void
    {
        $store = $this->getDocumentStore();
        try {

            $newSession = $store->openSession();
            try {
                $arr = new Arr();
                $arr->setArray(["a", 1, "b"]);

                $newSession->store($arr, "users/1");
                $changes = $newSession->advanced()->whatChanged();

                $this->assertCount(1, $changes);

                $this->assertCount(1, $changes['users/1']);
                $this->assertTrue($changes['users/1']->offsetGet(0)->getChange()->isDocumentAdded());

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                $arr = $newSession->load(Arr::class, "users/1");

                $arr->setArray(["a", 2, "c"]);

                $changes = $newSession->advanced()->whatChanged();
                $this->assertCount(1, $changes);
                $this->assertCount(2, $changes['users/1']);

                $this->assertTrue($changes['users/1']->offsetGet(0)->getChange()->isArrayValueChanged());
                $this->assertEquals(1, $changes['users/1']->offsetGet(0)->getFieldOldValue());
                $this->assertEquals(2, $changes['users/1']->offsetGet(0)->getFieldNewValue());

                $this->assertTrue($changes['users/1']->offsetGet(1)->getChange()->isArrayValueChanged());
                $this->assertEquals('b', $changes['users/1']->offsetGet(1)->getFieldOldValue());
                $this->assertEquals('c', $changes['users/1']->offsetGet(1)->getFieldNewValue());

            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_what_Changed_Array_Value_Added(): void
    {
        $store = $this->getDocumentStore();
        try {

            $newSession = $store->openSession();
            try {
                $arr = new Arr();
                $arr->setArray(["a", 1, "b"]);
                $newSession->store($arr, "arr/1");
                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                $arr = $newSession->load(Arr::class, "arr/1");

                $arr->setArray(["a", 1, "b", "c", 2]);

                $changes = $newSession->advanced()->whatChanged();
                $this->assertCount(1, $changes);
                $this->assertCount(2, $changes['arr/1']);

                $this->assertTrue($changes['arr/1']->offsetGet(0)->getChange()->isArrayValueAdded());
                $this->assertEquals('c', $changes['arr/1']->offsetGet(0)->getFieldNewValue());
                $this->assertEquals(null, $changes['arr/1']->offsetGet(0)->getFieldOldValue());

                $this->assertTrue($changes['arr/1']->offsetGet(1)->getChange()->isArrayValueAdded());
                $this->assertEquals(2, $changes['arr/1']->offsetGet(1)->getFieldNewValue());
                $this->assertEquals(null, $changes['arr/1']->offsetGet(1)->getFieldOldValue());
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_what_Changed_Array_Value_Removed(): void
    {
        $store = $this->getDocumentStore();
        try {
            $newSession = $store->openSession();
            try {
                $arr = new Arr();
                $arr->setArray(["a", 1, "b"]);
                $newSession->store($arr, "arr/1");
                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                $arr = $newSession->load(Arr::class, "arr/1");

                $arr->setArray(["a"]);

                $changes = $newSession->advanced()->whatChanged();
                $this->assertCount(1, $changes);
                $this->assertCount(2, $changes['arr/1']);

                $this->assertTrue($changes['arr/1']->offsetGet(0)->getChange()->isArrayValueRemoved());
                $this->assertEquals(1, $changes['arr/1']->offsetGet(0)->getFieldOldValue());
                $this->assertEquals(null, $changes['arr/1']->offsetGet(0)->getFieldNewValue());

                $this->assertTrue($changes['arr/1']->offsetGet(1)->getChange()->isArrayValueRemoved());
                $this->assertEquals('b', $changes['arr/1']->offsetGet(1)->getFieldOldValue());
                $this->assertEquals(null, $changes['arr/1']->offsetGet(1)->getFieldNewValue());
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_ravenDB_8169(): void
    {
        //Test that when old and new values are of different type
        //but have the same value, we consider them unchanged

        $store = $this->getDocumentStore();
        try {

            $newSession = $store->openSession();
            try {
                $anInt = new IntNumber();
                $anInt->setNumber(1);

                $newSession->store($anInt, "num/1");

                $aFloat = new FloatNumber();
                $aFloat->setNumber(2.0);
                $newSession->store($aFloat, "num/2");

                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                $newSession->load(FloatNumber::class, "num/1");
                $changes = $newSession->advanced()->whatChanged();
                $this->assertEmpty($changes);
            } finally {
                $newSession->close();
            }

            $newSession = $store->openSession();
            try {
                $newSession->load(IntNumber::class, "num/2");

                $changes = $newSession->advanced()->whatChanged();
                $this->assertEmpty($changes);
            } finally {
                $newSession->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_whatChanged_should_be_idempotent_operation(): void
    {
        //RavenDB-9150
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("user1");

                $user2 = new User();
                $user2->setName("user2");
                $user2->setAge(1);

                $user3 = new User();
                $user3->setName("user3");
                $user3->setAge(1);

                $session->store($user1, "users/1");
                $session->store($user2, "users/2");
                $session->store($user3, "users/3");

                $this->assertCount(3, $session->advanced()->whatChanged());

                $session->saveChanges();

                $user1 = $session->load(User::class, "users/1");
                $user2 = $session->load(User::class, "users/2");

                $user1->setAge(10);
                $session->delete($user2);

                $this->assertCount(2, $session->advanced()->whatChanged());
                $this->assertCount(2, $session->advanced()->whatChanged());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testHasChanges(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("user1");

                $user2 = new User();
                $user2->setName("user2");
                $user2->setAge(1);

                $session->store($user1, "users/1");
                $session->store($user2, "users/2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $this->assertFalse($session->advanced()->hasChanges());

                $u1 = $session->load(User::class, "users/1");
                $u2 = $session->load(User::class, "users/2");

                $this->assertFalse($session->advanced()->hasChanged($u1));
                $this->assertFalse($session->advanced()->hasChanged($u2));

                $u1->setName("new name");

                $this->assertTrue($session->advanced()->hasChanged($u1));
                $this->assertFalse($session->advanced()->hasChanged($u2));
                $this->assertTrue($session->advanced()->hasChanges());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
