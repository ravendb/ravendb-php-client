<?php

namespace tests\RavenDB\Documents\Commands;

use RavenDB\Documents\Commands\DeleteDocumentCommand;
use RavenDB\Exceptions\ConcurrencyException;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

// !status: DONE
class DeleteDocumentCommandTest  extends RemoteTestBase
{
    public function testCanDeleteDocument(): void
    {
        $store = $this->getDocumentStore();

        try  {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Marcin");
                $session->store($user, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }
            $command = new DeleteDocumentCommand("users/1");
            $store->getRequestExecutor()->execute($command);

            $session = $store->openSession();
            try {
                $loadedUser = $session->load(User::class, "users/1");
                $this->assertNull($loadedUser);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanDeleteDocumentByEtag(): void
    {
        $store = $this->getDocumentStore();

        try {
            $changeVector = null;

            $session = $store->openSession();

            try  {
                $user = new User();
                $user->setName("Marcin");
                $session->store($user, "users/1");
                $session->saveChanges();

                $changeVector = $session->advanced()->getChangeVectorFor($user);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $loadedUser = $session->load(User::class, "users/1");
                $loadedUser->setAge(5);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $command = new DeleteDocumentCommand("users/1", $changeVector);

            $this->expectException(ConcurrencyException::class);

            $store->getRequestExecutor()->execute($command);
        } finally {
            $store->close();
        }
    }
}
