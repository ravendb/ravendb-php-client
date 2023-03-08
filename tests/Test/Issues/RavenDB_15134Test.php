<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Operations\Counters\GetCountersOperation;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15134Test extends RemoteTestBase
{
    public function testGetCountersOperationShouldReturnNullForNonExistingCounter(): void
    {
        $store = $this->getDocumentStore();
        try {
            $docId = "users/1";

            $session = $store->openSession();
            try {
                $session->store(new User(), $docId);

                $c = $session->countersFor($docId);

                $c->increment("likes");
                $c->increment("dislikes", 2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $vals = $store->operations()->send(new GetCountersOperation($docId, [ "likes", "downloads", "dislikes" ]));
            $this->assertCount(3, $vals->getCounters());

            $this->assertContains(null, $vals->getCounters());

            $this->assertContains(true, array_map(function ($x) { return $x != null && $x->getTotalValue() == 1;}, $vals->getCounters()->getArrayCopy()));
            $this->assertContains(true, array_map(function ($x) { return $x != null && $x->getTotalValue() == 2;}, $vals->getCounters()->getArrayCopy()));

            $vals = $store->operations()->send(new GetCountersOperation($docId, [ "likes", "downloads", "dislikes" ], true));
            $this->assertCount(3, $vals->getCounters());

            $this->assertContains(null, $vals->getCounters());

            $this->assertContains(true, array_map(function ($x) { return ($x != null) && ($x->getTotalValue() == 1);}, $vals->getCounters()->getArrayCopy()));
        } finally {
            $store->close();
        }
    }
}
