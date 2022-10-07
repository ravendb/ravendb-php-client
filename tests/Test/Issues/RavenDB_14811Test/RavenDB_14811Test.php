<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14811Test;

use RavenDB\Documents\Queries\QueryData;
use tests\RavenDB\RemoteTestBase;

class RavenDB_14811Test extends RemoteTestBase
{
    public function testCan_Project_Id_Field_In_Class(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user = new User();
            $user->setName("Grisha");
            $user->setAge(34);

            $session = $store->openSession();
            try {
               $session->store($user);
               $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var UserProjectionIntId $result */
                $result = $session->query(User::class)
                        ->selectFields(UserProjectionIntId::class, "name")
                        ->firstOrDefault();

                $this->assertNotNull($result);
                $this->assertEquals(0, $result->getId());
                $this->assertEquals($user->getName(), $result->getName());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var UserProjectionIntId $result */
                $result = $session->query(User::class)
                        ->selectFields(UserProjectionIntId::class, new QueryData([ "id" ], [ "name" ]))
                        ->firstOrDefault();

                $this->assertNotNull($result);
                $this->assertEquals(0, $result->getId());
                $this->assertEquals($user->getId(), $result->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCan_project_id_field(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user = new User();
            $user->setName("Grisha");
            $user->setAge(34);

            $session = $store->openSession();
            try {
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var UserProjectionIntId $result */
                $result = $session->query(User::class)
                        ->selectFields(UserProjectionIntId::class, "name")
                        ->firstOrDefault();

                $this->assertNotNull($result);
                $this->assertEquals($user->getName(), $result->getName());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var UserProjectionIntId $result */
                $result = $session->query(User::class)
                        ->selectFields(UserProjectionIntId::class, new QueryData(["age", "name"], ["id", "name"]))
                        ->firstOrDefault();

                $this->assertNotNull($result);
                $this->assertEquals($user->getAge(), $result->getId());
                $this->assertEquals($user->getName(), $result->getName());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var UserProjectionStringId $result */
                $result = $session->query(User::class)
                        ->selectFields(UserProjectionStringId::class, "id", "name")
                        ->firstOrDefault();

                $this->assertNotNull($result);
                $this->assertEquals($user->getId(), $result->getId());
                $this->assertEquals($user->getName(), $result->getName());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var UserProjectionStringId $result */
                $result = $session->query(User::class)
                        ->selectFields(UserProjectionStringId::class, new QueryData(["name", "name"], ["id", "name"]))
                        ->firstOrDefault();

                $this->assertNotNull($result);
                $this->assertEquals($user->getName(), $result->getId());
                $this->assertEquals($user->getName(), $result->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
