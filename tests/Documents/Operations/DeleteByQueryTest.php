<?php

namespace tests\RavenDB\Documents\Operations;

use tests\RavenDB\RemoteTestBase;
use RavenDB\Documents\Queries\IndexQuery;
use tests\RavenDB\Infrastructure\Entity\User;
use RavenDB\Documents\Operations\DeleteByQueryOperation;

class DeleteByQueryTest extends RemoteTestBase
{
    public function testCanDeleteByQuery(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setAge(5);
                $session->store($user1);

                $user2 = new User();
                $user2->setAge(10);
                $session->store($user2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $indexQuery = new IndexQuery();
            $indexQuery->setQuery("from users where age == 5");
            $operation = new DeleteByQueryOperation($indexQuery);
            $asyncOp = $store->operations()->sendAsync($operation);

            $asyncOp->waitForCompletion();

            $session = $store->openSession();
            try {
                $this->assertEquals(1, $session->query(User::class)->count());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
