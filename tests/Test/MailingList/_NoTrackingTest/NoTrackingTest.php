<?php

namespace tests\RavenDB\Test\MailingList\_NoTrackingTest;

use tests\RavenDB\RemoteTestBase;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Session\BeforeQueryEventArgs;

class NoTrackingTest extends RemoteTestBase
{
    public function testCanLoadEntitiesWithNoTracking(): void
    {
        $store = $this->getDocumentStore();
        try {

            $this->createData($store);

            $session = $store->openSession();
            try {
                $session->advanced()->addBeforeQueryListener(function ($sender, BeforeQueryEventArgs $handler) {
                    $handler->getQueryCustomization()->noTracking();
                });
                /** @var AA[] $result */
                $result = $session->query(AA::class)
                    ->include("bs")
                    ->toList();

                $this->assertCount(1, $result);

                foreach ($result[0]->getBs() as $key => $value) {
                    unset($result[0]->getBs()[$key]);
                }

                $this->assertFalse($session->advanced()->hasChanged($result[0]));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    private static function createData(DocumentStoreInterface $store): void
    {
        $session = $store->openSession();
        try {
            $a = new AA();
            $a->setId("a/1");

            $b = new B();
            $b->setId("b/1");

            $a->getBs()[] = "b/1";

            $session->store($a);
            $session->store($b);
            $session->saveChanges();
        } finally {
            $session->close();
        }
    }
}
