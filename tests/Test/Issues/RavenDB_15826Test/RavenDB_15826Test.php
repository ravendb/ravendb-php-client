<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15826Test;

use tests\RavenDB\RemoteTestBase;

class RavenDB_15826Test extends RemoteTestBase
{
    public function testCanIncludeLazyLoadITemThatIsAlreadyOnSession(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $session->store(new Item(), "items/a");
                $session->store(new Item(), "items/b");
                $itemC = new Item();
                $itemC->setRefs([ "items/a", "items/b" ]);
                $session->store($itemC, "items/c");
                $itemD = new Item();
                $itemD->setRefs([ "items/a" ]);
                $session->store($itemD, "items/d");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->include("refs")->load(Item::class, "items/d"); // include, some loaded
                /** @var Item $a */
                $a = $session->load(Item::class, "items/c");// include, some loaded
                $items = $session->advanced()->lazily()->load(Item::class, $a->getRefs());
                $session->advanced()->eagerly()->executeAllPendingLazyOperations();
                $itemsMap = $items->getValue();
                $this->assertEquals(count($a->getRefs()), count($itemsMap));
                $array = array_filter($itemsMap->getArrayCopy(), function($x) { return $x == null; });
                $this->assertEmpty($array);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
