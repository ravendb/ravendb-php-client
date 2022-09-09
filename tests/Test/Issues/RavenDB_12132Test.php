<?php

namespace tests\RavenDB\Test\Issues;

use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Infrastructure\Entity\User;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Documents\Session\TransactionMode;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeResult;
use RavenDB\Documents\Operations\CompareExchange\PutCompareExchangeValueOperation;

// !status: DONE
class RavenDB_12132Test extends RemoteTestBase
{
    public function testCanPutObjectWithId(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user = new User();
            $user->setId("users/1");
            $user->setName("Grisha");

            /** @var CompareExchangeResult $res */
            $res = $store->operations()->send(new PutCompareExchangeValueOperation("test", $user, 0));

            $this->assertTrue($res->isSuccessful());

            $this->assertEquals("Grisha", $res->getValue()->getName());
            $this->assertEquals("users/1", $res->getValue()->getId());
        } finally {
            $store->close();
        }
    }

    public function testCanCreateClusterTransactionRequest1(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user = new User();
            $user->setId("this/is/my/id");
            $user->setName("Grisha");

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()
                        ->createCompareExchangeValue("usernames/ayende", $user);
                $session->saveChanges();

                /** @var User $userFromCluster */
                $userFromCluster = $session->advanced()
                        ->clusterTransaction()
                        ->getCompareExchangeValue(User::class,"usernames/ayende")->getValue();
                $this->assertEquals($user->getName(), $userFromCluster->getName());
                $this->assertEquals($user->getId(), $userFromCluster->getId());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
