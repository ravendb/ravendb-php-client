<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Operations\Counters\GetCountersOperation;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15313Test extends RemoteTestBase
{
    public function testGetCountersOperationShouldFilterDuplicateNames(): void
    {
        $store = $this->getDocumentStore();
        try {
            $docId = "users/1";

            $names = ["likes", "dislikes", "likes", "downloads", "likes", "downloads"];

            $session = $store->openSession();
            try {
                $session->store(new User(), $docId);

                $cf = $session->countersFor($docId);

                for ($i = 0; $i < count($names); $i++) {
                    $cf->increment($names[$i], $i);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $vals = $store->operations()->send(new GetCountersOperation($docId, $names));

            $this->assertCount(3, $vals->getCounters());

            $expected = 6; // likes
            $this->assertEquals($expected, $vals->getCounters()[0]->getTotalValue());

            $expected = 1; // dislikes

            $this->assertEquals($expected, $vals->getCounters()[1]->getTotalValue());

            $expected = 8;
            $this->assertEquals($expected, $vals->getCounters()[2]->getTotalValue());
        } finally {
            $store->close();
        }
    }

    public function testGetCountersOperationShouldFilterDuplicateNames_PostGet(): void
    {
        $store = $this->getDocumentStore();
        try {
            $docId = "users/1";

            $names = array_fill(0, 1024, '');
            $dict = [];

            $session = $store->openSession();
            try {
                $session->store(new User(), $docId);

                $cf = $session->countersFor($docId);

                for ($i = 0; $i < 1024; $i++) {
                    $name = '';
                    if ($i % 4 == 0) {
                        $name = "abc";
                    } else if ($i % 10 == 0) {
                        $name = "xyz";
                    } else {
                        $name = "likes" . $i;
                    }

                    $names[$i] = $name;

                    $oldVal = array_key_exists($name, $dict) ? $dict[$name] : null;
                    $dict[$name] = $oldVal != null ? $oldVal + $i : $i;

                    $cf->increment($name, $i);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $vals = $store->operations()->send(new GetCountersOperation($docId, $names));

            $expectedCount = count($dict);

            $this->assertCount($expectedCount, $vals->getCounters());

            $namesList = $names;
            $hs = array_unique($namesList);
            $expectedVals = array_values(
                array_map(
                    function ($x) use ($dict) {
                        return $dict[$x];
                    },
                    array_filter(
                        $namesList,
                        function ($x) use (& $hs) {
                            $idx = array_search($x, $hs);
                            if ($idx === false)
                                return false;
                            unset($hs[$idx]);
                            return true;
                        }
                    )
                )
            );

            for ($i = 0; $i < count($vals->getCounters()); $i++) {
                $this->assertEquals($vals->getCounters()[$i]->getTotalValue(), $expectedVals[$i]);
            }
        } finally {
            $store->close();
        }
    }
}
