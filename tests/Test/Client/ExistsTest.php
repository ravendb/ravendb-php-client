<?php

namespace tests\RavenDB\Test\Client;

use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

// !status: DONE
class ExistsTest extends RemoteTestBase
{
    public function testCheckIfDocumentExists(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $idan = new User();
                $idan->setName("Idan");

                $shalom = new User();
                $shalom->setName("Shalom");

                $session->store($idan, "users/1");
                $session->store($shalom, "users/2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $this->assertTrue($session->advanced()->exists("users/1"));
                $this->assertFalse($session->advanced()->exists("users/10"));

                $session->load(User::class, "users/2");
                $this->assertTrue($session->advanced()->exists("users/2"));

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
