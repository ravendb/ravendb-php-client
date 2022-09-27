<?php

namespace tests\RavenDB\Test\Session\_AddOrPatchTest;

use DateTime;
use RavenDB\Utils\DateUtils;
use RavenDB\Type\DateTimeArray;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\RavenTestHelper;
use RavenDB\Documents\Session\JavaScriptArray;

class AddOrPatchTest extends RemoteTestBase
{
    public function testCanAddOrPatch(): void
    {
        $store = $this->getDocumentStore();
        try {

            $id = "users/1";

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setFirstName("Hibernating");
                $user->setLastName("Rhinos");
                $user->setLastLogin(DateUtils::now());
                $session->store($user, $id);
                $session->saveChanges();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $newUser = new User();
                $newUser->setFirstName("Hibernating");
                $newUser->setLastName("Rhinos");
                $newUser->setLastLogin(DateUtils::now());

                $newDate = DateUtils::setYears(RavenTestHelper::utcToday(), 1993);

                $session->advanced()->addOrPatch($id, $newUser, "lastLogin", $newDate);
                $session->saveChanges();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $session->delete($id);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $newUser = new User();
                $newUser->setFirstName("Hibernating");
                $newUser->setLastName("Rhinos");
                $newUser->setLastLogin(DateUtils::unixEpochStart());

                $newDate = RavenTestHelper::utcToday();
                $newDate->setDate(1993, $newDate->format('m'), $newDate->format('d'));

                $session->advanced()->addOrPatch($id, $newUser, "lastLogin", $newDate);
                $session->saveChanges();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                /** @var User $user */
                $user = $session->load(User::class, $id);
                $this->assertEquals("Hibernating", $user->getFirstName());
                $this->assertEquals("Rhinos", $user->getLastName());
                $this->assertEquals(DateUtils::unixEpochStart(), $user->getLastLogin());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanAddOrPatchAddItemToAnExistingArray(): void
    {
        $store = $this->getDocumentStore();
        try {

            $id = "users/1";

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setFirstName("Hibernating");
                $user->setLastName("Rhinos");

                $d2000 = DateUtils::setYears(RavenTestHelper::utcToday(), 2000);

                $user->setLoginTimes(DateTimeArray::fromArray([$d2000]));
                $session->store($user, $id);
                $session->saveChanges();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $newUser = new User();
                $newUser->setFirstName("Hibernating");
                $newUser->setLastName("Rhinos");
                $newUser->setLoginTimes(DateTimeArray::fromArray([DateUtils::now()]));

                $d1993 = DateUtils::setYears(RavenTestHelper::utcToday(), 1993);
                $d2000 = DateUtils::setYears(RavenTestHelper::utcToday(), 2000);

                $session->advanced()->addOrPatchArray($id, $newUser, "loginTimes", function(JavaScriptArray &$u) use ($d1993, $d2000) {
                    $u->add($d1993, $d2000);
                });

                $session->saveChanges();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                /** @var User $user */
                $user = $session->load(User::class, $id);
                $this->assertCount(3, $user->getLoginTimes());

                $session->delete($id);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $now = DateUtils::now();

                $newUser = new User();
                $newUser->setLastName("Hibernating");
                $newUser->setFirstName("Rhinos");
                $newUser->setLastLogin($now);

                $d1993 = DateUtils::setYears(RavenTestHelper::utcToday(), 1993);

                $session->advanced()->addOrPatch($id, $newUser, "lastLogin", $d1993);

                $session->saveChanges();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                /** @var User $user */
                $user = $session->load(User::class, $id);
                $this->assertEquals("Hibernating", $user->getLastName());
                $this->assertEquals("Rhinos", $user->getFirstName());
                $this->assertEqualsWithDelta($now, $user->getLastLogin(), 0.000999);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanAddOrPatchIncrement(): void
    {
        $store = $this->getDocumentStore();
        try {

            $id = "users/1";

            $session = $store->openSession();
            try {
                $newUser = new User();
                $newUser->setFirstName("Hibernating");
                $newUser->setLastName("Rhinos");
                $newUser->setLoginCount(1);

                $session->store($newUser, $id);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $newUser = new User();
                $newUser->setFirstName("Hibernating");
                $newUser->setLastName("Rhinos");
                $session->advanced()->addOrIncrement($id, $newUser, "loginCount", 3);

                $session->saveChanges();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                /** @var User $user */
                $user = $session->load(User::class, $id);
                $this->assertEquals(4, $user->getLoginCount());

                $session->delete($id);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $newUser = new User();
                $newUser->setFirstName("Hibernating");
                $newUser->setLastName("Rhinos");
                $newUser->setLastLogin(DateUtils::unixEpochStart());

                $d1993 = DateUtils::setYears(RavenTestHelper::utcToday(), 1993);

                $session->advanced()->addOrPatch($id, $newUser, "lastLogin", $d1993);

                $session->saveChanges();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                /** @var User $user */
                $user = $session->load(User::class, $id);
                $this->assertEquals("Hibernating", $user->getFirstName());
                $this->assertEquals("Rhinos", $user->getLastName());
                $this->assertEquals(DateUtils::unixEpochStart(), $user->getLastLogin());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
