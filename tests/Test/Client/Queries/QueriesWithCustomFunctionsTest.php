<?php

namespace tests\RavenDB\Test\Client\Queries;

use RavenDB\Documents\Operations\CompareExchange\PutCompareExchangeValueOperation;
use RavenDB\Documents\Session\CmpXchg;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class QueriesWithCustomFunctionsTest extends RemoteTestBase
{
    public function testQueryCmpXchgWhere(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->operations()->send(new PutCompareExchangeValueOperation("Tom", "Jerry", 0));
            $store->operations()->send(new PutCompareExchangeValueOperation("Hera", "Zeus", 0));
            $store->operations()->send(new PutCompareExchangeValueOperation("Gaya", "Uranus", 0));
            $store->operations()->send(new PutCompareExchangeValueOperation("Jerry@gmail.com", "users/2", 0));
            $store->operations()->send(new PutCompareExchangeValueOperation("Zeus@gmail.com", "users/1", 0));

            $session = $store->openSession();
            try {
                $jerry = new User();
                $jerry->setName("Jerry");
                $session->store($jerry, "users/2");
                $session->saveChanges();

                $zeus = new User();
                $zeus->setName("Zeus");
                $zeus->setLastName("Jerry");
                $session->store($zeus, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $q = $session->advanced()
                        ->documentQuery(User::class)
                        ->whereEquals("name", CmpXchg::value("Hera"))
                        ->whereEquals("lastName", CmpXchg::value("Tom"));

                $this->assertEquals('from \'Users\' where name = cmpxchg($p0) and lastName = cmpxchg($p1)', $q->getIndexQuery()->getQuery());

                /** @var User[] $queryResult */
                $queryResult = $q->toList();

                $this->assertCount(1, $queryResult);
                $this->assertEquals("Zeus", $queryResult[0]->getName());

                /** @var User[] $user */
                $user = $session->advanced()->documentQuery(User::class)
                        ->whereNotEquals("name", CmpXchg::value("Hera"))
                        ->toList();

                $this->assertCount(1, $user);
                $this->assertEquals("Jerry", $user[0]->getName());

                /** @var User[] $users */
                $users = $session->advanced()->rawQuery(User::class, "from Users where name = cmpxchg(\"Hera\")")
                        ->toList();

                $this->assertCount(1, $users);
                $this->assertEquals("Zeus", $users[0]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
