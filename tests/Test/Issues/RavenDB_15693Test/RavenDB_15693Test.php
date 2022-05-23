<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15693Test;

use tests\RavenDB\RemoteTestBase;

class RavenDB_15693Test extends RemoteTestBase
{
    public function testCanQueryOnComplexBoost(): void
    {
        $store = $this->getDocumentStore();
        try {

            $s = $store->openSession();
            try {
                $q = $s->advanced()->documentQuery(Doc::class)
                    ->search("strVal1", "a")
                    ->andAlso()
                    ->openSubclause()
                    ->search("strVal2", "b")
                    ->orElse()
                    ->search("strVal3", "search")
                    ->closeSubclause()
                    ->boost(0.2);

                $queryBoost = $q->toString();

                $this->assertEquals('from \'Docs\' where search(strVal1, $p0) and boost(search(strVal2, $p1) or search(strVal3, $p2), $p3)', $queryBoost);

                $q->toList();
            } finally {
                $s->close();
            }
        } finally {
            $store->close();
        }
    }
}
