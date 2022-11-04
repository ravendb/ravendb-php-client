<?php

namespace tests\RavenDB\Test\Client;

use RavenDB\Documents\Operations\PatchByQueryOperation;
use RavenDB\Exceptions\Documents\Patching\JavaScriptException;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Infrastructure\Entity\User;
use RavenDB\Documents\Operations\PatchStatus;
use RavenDB\Documents\Operations\PatchRequest;
use RavenDB\Documents\Operations\PatchOperation;
use RavenDB\Documents\Session\IndexesWaitOptsBuilder;
use tests\RavenDB\Test\Client\Indexing\_IndexesFromClientTest\Users_ByName;

class PatchTest extends RemoteTestBase
{
    public function testCanPatchSingleDocument(): void
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

            $patchOperation = new PatchOperation("users/1", null,
                    PatchRequest::forScript("this.name = \"Patched\""));
            /** @var PatchStatus $status */
            $status = $store->operations()->send($patchOperation);
            $this->assertTrue($status->isPatched());

            $session = $store->openSession();
            try {
                $loadedUser = $session->load(User::class, "users/1");

                $this->assertEquals("Patched", $loadedUser->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    /** @doesNotPerformAssertions */
    public function testCanWaitForIndexAfterPatch(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new Users_ByName())->execute($store);

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");

                $session->store($user, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->waitForIndexesAfterSaveChanges(function(IndexesWaitOptsBuilder $x) {
                    $x->waitForIndexes("Users/ByName");
                });

                /** @var User $user */
                $user = $session->load(User::class, "users/1");
                $session->advanced()->patch($user, "name", "New Name");
                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    // @todo: implement this test
    public function canPatchManyDocuments(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");

                $session->store($user, "users/1");
                $session->saveChanges();

//                $this->assertEquals(1, $session->query(User::class)->countLazily()->getValue());
            } finally {
                $session->close();
            }

//            $operation = new PatchByQueryOperation("from Users update {  this.name= \"Patched\"  }");
//
//            Operation op = store.operations().sendAsync(operation);
//
//            op.waitForCompletion();

            $session = $store->openSession();
            try {
//                User loadedUser = session.load(User.class, "users/1");
//
//                assertThat(loadedUser.getName())
//                        .isEqualTo("Patched");
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testThrowsOnInvalidScript(): void
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

            $operation = new PatchByQueryOperation("from Users update {  throw 5 }");

            $op = $store->operations()->sendAsync($operation);

            $this->expectException(JavaScriptException::class);
            $op->waitForCompletion();
        } finally {
            $store->close();
        }
    }
}
