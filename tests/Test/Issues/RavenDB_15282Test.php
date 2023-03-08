<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Operations\Counters\GetCountersOperation;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15282Test extends RemoteTestBase
{
    public function testCountersPostGetReturnFullResults(): void
    {
        $store = $this->getDocumentStore();
        try {
            $docId = "users/1";
            $counterNames = [];

            $session = $store->openSession();
            try {
                $session->store(new User(), $docId);

                $c = $session->countersFor($docId);

                for ($i = 0; $i < 1000; $i++) {
                    $name = "likes" . $i;
                    $counterNames[$i] = $name;
                    $c->increment($name);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $vals = $store->operations()->send(new GetCountersOperation($docId, $counterNames, true));
            $this->assertCount(1000, $vals->getCounters());

            for ($i = 0; $i < 1000; $i++) {
                $this->assertCount(1, $vals->getCounters()[$i]->getCounterValues());

                $values = $vals->getCounters()[$i]->getCounterValues();
                $this->assertEquals(1, $values[array_key_first($values)]);
            }
        } finally {
            $store->close();
        }
    }
}
