<?php

namespace tests\RavenDB\Test\Client\_FirstClassPatchTest;

use DateTime;
use RavenDB\Documents\Session\DocumentSession;
use RavenDB\Documents\Session\DocumentSessionInterface;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Documents\Session\JavaScriptArray;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Type\ObjectMap;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\RemoteTestBase;

class FirstClassPatchTest extends RemoteTestBase
{
    private string $docId = "users/1-A";

    public function testCanPatch(): void
    {
        $stuff = new StuffArray();
        $s = new Stuff();
        $s->setKey(6);
        $stuff->append($s);

        $user = new User();
        $user->setNumbers([66]);
        $user->setStuff($stuff);

        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $now = DateUtils::now();

            $session = $store->openSession();
            try {
                $session->advanced()->patch($this->docId, "numbers[0]", 31);
                $session->advanced()->patch($this->docId, "lastLogin", $now);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var User $loaded */
                $loaded = $session->load(User::class, $this->docId);
                $this->assertEquals(31, $loaded->getNumbers()[0]);
                // @todo: check with Marcin is this the way we can set delta
                $this->assertEqualsWithDelta($now, $loaded->getLastLogin(), 0.000999);

                $session->advanced()->patch($loaded, "stuff[0].phone", "123456");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var User $loaded */
                $loaded = $session->load(User::class, $this->docId);
                $this->assertEquals('123456', $loaded->getStuff()[0]->getPhone());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanPatchAndModify(): void
    {
        $user = new User();
        $user->setNumbers([66]);

        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var User $loaded */
                $loaded = $session->load(User::class, $this->docId);
                $loaded->getNumbers()[0] = 1;
                $session->advanced()->patch($loaded, "numbers[0]", 2);

                $this->expectException(IllegalStateException::class);
                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanPatchComplex(): void
    {
        $stuff = new StuffArray();
        $s = new Stuff();
        $s->setKey(6);
        $stuff->append($s);

        $user = new User();
        $user->setStuff($stuff);

        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $newStuff = new Stuff();
                $newStuff->setKey(4);
                $newStuff->setPhone("9255864406");
                $newStuff->setFriend(new Friend());
                $session->advanced()->patch($this->docId, "stuff[1]", $newStuff);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var User $loaded */
                $loaded = $session->load(User::class, $this->docId);

                $this->assertEquals('9255864406', $loaded->getStuff()[1]->getPhone());
                $this->assertEquals(4, $loaded->getStuff()[1]->getKey());
                $this->assertNotNull($loaded->getStuff()[1]->getFriend());

                $pet1 = new Pet();
                $pet1->setKind("Dog");
                $pet1->setName("Hanan");

                $friendsPet = new Pet();
                $friendsPet->setName("Miriam");
                $friendsPet->setKind("Cat");

                $friend = new Friend();
                $friend->setName("Gonras");
                $friend->setAge(28);
                $friend->setPet($friendsPet);

                $secondStuff = new Stuff();
                $secondStuff->setKey(4);
                $secondStuff->setPhone("9255864406");
                $secondStuff->setPet($pet1);
                $secondStuff->setFriend($friend);

                $map = [];
                $map["Ohio"] = "Columbus";
                $map["Utah"] = "Salt Lake City";
                $map["Texas"] = "Austin";
                $map["California"] = "Sacramento";

                $secondStuff->setDic($map);

                $session->advanced()->patch($loaded, "stuff[2]", $secondStuff);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var User $loaded */
                $loaded = $session->load(User::class, $this->docId);

                $this->assertEquals("Hanan", $loaded->getStuff()[2]->getPet()->getName());
                $this->assertEquals("Gonras", $loaded->getStuff()[2]->getFriend()->getName());
                $this->assertEquals("Miriam", $loaded->getStuff()[2]->getFriend()->getPet()->getName());

                $this->assertCount(4, $loaded->getStuff()[2]->getDic());
                $this->assertEquals("Salt Lake City", $loaded->getStuff()[2]->getDic()['Utah']);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanAddToArray(): void
    {
        $stuff = new StuffArray();

        $s = new Stuff();
        $s->setKey(6);
        $stuff->append($s);

        $user = new User();
        $user->setStuff($stuff);
        $user->setNumbers([1, 2]);

        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                //push
                $session->advanced()->patchArray($this->docId, "numbers", function(JavaScriptArray &$roles) {
                    $roles->add(3);
                });

                $session->advanced()->patchArray($this->docId, "stuff", function(JavaScriptArray &$roles) {
                    $stuff1 = new Stuff();
                    $stuff1->setKey(75);
                    $roles->add($stuff1);
                });
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var User $loaded */
                $loaded = $session->load(User::class, $this->docId);

                $this->assertEquals(3, $loaded->getNumbers()[2]);
                $this->assertEquals(75, $loaded->getStuff()[1]->getKey());

                //concat
                $session->advanced()->patchArray($loaded, "numbers", function(JavaScriptArray &$roles) {
                    $roles->add(101, 102, 103);
                });

                $session->advanced()->patchArray($loaded, "stuff", function(JavaScriptArray &$roles) {
                    $s1 = new Stuff();
                    $s1->setKey(102);

                    $s2 = new Stuff();
                    $s2->setPhone("123456");

                    $roles
                        ->add($s1)
                        ->add($s2);
                });
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var User $loaded */
                $loaded = $session->load(User::class, $this->docId);

                $this->assertCount(6, $loaded->getNumbers());
                $this->assertEquals(103, $loaded->getNumbers()[5]);

                $this->assertEquals(102, $loaded->getStuff()[2]->getKey());
                $this->assertEquals('123456', $loaded->getStuff()[3]->getPhone());

                $session->advanced()->patchArray($loaded, "numbers", function(JavaScriptArray &$roles) {
                    $roles->add([201, 202, 203]);
                });
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var User $loaded */
                $loaded = $session->load(User::class, $this->docId);
                $this->assertCount(9, $loaded->getNumbers());
                $this->assertEquals(202, $loaded->getNumbers()[7]);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanRemoveFromArray(): void
    {
        $stuff = new StuffArray();

        $s0 = new Stuff();
        $s0->setKey(6);
        $stuff->append($s0);

        $s1 = new Stuff();
        $s1->setPhone('123456');
        $stuff->append($s1);

        $user = new User();
        $user->setStuff($stuff);
        $user->setNumbers([ 1, 2, 3 ]);

        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->patchArray($this->docId, "numbers", function(JavaScriptArray &$roles) {
                    $roles->removeAt(1);
                });
                $session->advanced()->patchArray($this->docId, "stuff", function(JavaScriptArray &$roles) {
                    $roles->removeAt(0);
                });

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var User $loaded */
                $loaded = $session->load(User::class, $this->docId);
                $this->assertCount(2, $loaded->getNumbers());
                $this->assertEquals(3, $loaded->getNumbers()[1]);

                $this->assertCount(1, $loaded->getStuff());
                $this->assertEquals('123456', $loaded->getStuff()[0]->getPhone());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanIncrement(): void
    {
        $stuff = new StuffArray();

        $s = new Stuff();
        $s->setKey(6);
        $stuff->append($s);

        $user = new User();
        $user->setNumbers([ 66 ]);
        $user->setStuff($stuff);

        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->increment($this->docId, "numbers[0]", 1);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var User $loaded */
                $loaded = $session->load(User::class, $this->docId);
                $this->assertEquals(67, $loaded->getNumbers()[0]);

                $session->advanced()->increment($loaded, "stuff[0].key", -3);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $loaded = $session->load(User::class, $this->docId);
                $this->assertEquals(3, $loaded->getStuff()[0]->getKey());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testShouldMergePatchCalls(): void
    {
        $stuff = new StuffArray();

        $s = new Stuff();
        $s->setKey(6);
        $stuff->append($s);

        $user = new User();
        $user->setStuff($stuff);
        $user->setNumbers([ 66 ]);

        $user2 = new User();
        $user2->setNumbers([ 1, 2,3 ]);
        $user2->setStuff($stuff);

        $docId2 = "users/2-A";

        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $session->store($user);
                $session->store($user2, $docId2);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $now = DateUtils::now();

            /** @var InMemoryDocumentSessionOperations|DocumentSessionInterface $session */
            $session = $store->openSession();
            try {
                $session->advanced()->patch($this->docId, "numbers[0]", 31);
                $this->assertEquals(1, $session->getDeferredCommandsCount());

                $session->advanced()->patch($this->docId, "lastLogin", $now);
                $this->assertEquals(1, $session->getDeferredCommandsCount());

                $session->advanced()->patch($docId2, "numbers[0]", 123);
                $this->assertEquals(2, $session->getDeferredCommandsCount());

                $session->advanced()->patch($docId2, "lastLogin", $now);
                $this->assertEquals(2, $session->getDeferredCommandsCount());

                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var InMemoryDocumentSessionOperations|DocumentSessionInterface $session */
            $session = $store->openSession();
            try {
                $session->advanced()->increment($this->docId, "numbers[0]", 1);
                $this->assertEquals(1, $session->getDeferredCommandsCount());

                $session->advanced()->patchArray($this->docId, "numbers", function(JavaScriptArray &$r) {
                    $r->add(77);
                });
                $this->assertEquals(1, $session->getDeferredCommandsCount());

                $session->advanced()->patchArray($this->docId, "numbers", function(JavaScriptArray &$r) {
                    $r->add(88);
                });
                $this->assertEquals(1, $session->getDeferredCommandsCount());

                $session->advanced()->patchArray($this->docId, "numbers", function(JavaScriptArray &$r) {
                    $r->removeAt(1);
                });
                $this->assertEquals(1, $session->getDeferredCommandsCount());

                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
