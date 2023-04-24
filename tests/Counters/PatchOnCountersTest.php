<?php

namespace tests\RavenDB\Counters;

use RavenDB\Documents\Operations\Counters\GetCountersOperation;
use RavenDB\Documents\Operations\PatchOperation;
use RavenDB\Documents\Operations\PatchRequest;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class PatchOnCountersTest extends RemoteTestBase
{
    public function testCanIncrementSingleCounter(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");

                $session->store($user, "users/1-A");

                $session->countersFor("users/1-A")
                        ->increment("Downloads", 100);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $patch1 = new PatchRequest();
            $patch1->setScript("incrementCounter(this, args.name, args.val)");
            $values = [];
            $values["name"] = "Downloads";
            $values["val"] = 100;
            $patch1->setValues($values);
            $store->operations()->send(new PatchOperation("users/1-A", null, $patch1));

            $totalValue = $store->operations()->send(new GetCountersOperation("users/1-A", [ "Downloads" ]))
                ->getCounters()[0]->getTotalValue();
            $this->assertEquals(200, $totalValue);
        } finally {
            $store->close();
        }
    }
}
