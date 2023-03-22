<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Commands\GetDocumentsCommand;
use RavenDB\Documents\Operations\Counters\GetCountersOperation;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_14919Test extends RemoteTestBase
{
    public function testGetCountersOperationShouldDiscardNullCounters(): void
    {
        $store = $this->getDocumentStore();
        try {
            $docId = "users/2";

            $counterNames = array_fill(0, 124, '');

            $session = $store->openSession();
            try {
                $session->store(new User(), $docId);

                $c = $session->countersFor($docId);
                for ($i = 0; $i < 100; $i++) {
                    $name = "likes" . $i;
                    $counterNames[$i] = $name;
                    $c->increment($name);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $vals = $store->operations()->send(new GetCountersOperation($docId, $counterNames));
            $this->assertCount(101, $vals->getCounters());

            for ($i = 0; $i < 100; $i++) {
                $this->assertEquals(1, $vals->getCounters()[$i]->getTotalValue());
            }

            $this->assertNull($vals->getCounters()[count($vals->getCounters()) - 1]);

            // test with returnFullResults = true
            $vals = $store->operations()->send(new GetCountersOperation($docId, $counterNames, true));

            $this->assertCount(101, $vals->getCounters());

            for ($i = 0; $i < 100; $i++) {
                $this->assertCount(1, $vals->getCounters()[$i]->getCounterValues());
            }

            $this->assertNull($vals->getCounters()[count($vals->getCounters()) - 1]);
        } finally {
            $store->close();
        }
    }

    public function testGetCountersOperationShouldDiscardNullCounters_PostGet(): void
    {
        $store = $this->getDocumentStore();
        try {
            $docId = "users/2";
            $counterNames = array_fill(0, 1024, '');

            $session = $store->openSession();
            try {
                $session->store(new User(), $docId);

                $c = $session->countersFor($docId);

                for ($i = 0; $i < 1000; $i++) {
                    $name = "likes" . $i;
                    $counterNames[$i] = $name;
                    $c->increment($name, $i);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $vals = $store->operations()->send(new GetCountersOperation($docId, $counterNames));
            $this->assertCount(1001, $vals->getCounters());

            for ($i = 0; $i < 1000; $i++) {
                $this->assertEquals($i, $vals->getCounters()[$i]->getTotalValue());
            }

            $this->assertNull($vals->getCounters()[count($vals->getCounters()) - 1]);

            // test with returnFullResults = true
            $vals = $store->operations()->send(new GetCountersOperation($docId, $counterNames, true));
            $this->assertCount(1001, $vals->getCounters());

            for ($i = 0; $i < 1000; $i++) {
                $this->assertEquals($i,$vals->getCounters()[$i]->getTotalValue());
            }

            $this->assertNull($vals->getCounters()[count($vals->getCounters()) - 1]);
        } finally {
            $store->close();
        }
    }

    public function testGetDocumentsCommandShouldDiscardNullIds(): void
    {
        $store = $this->getDocumentStore();
        try {
            $ids = array_fill(0, 124, null);

            $session = $store->openSession();
            try {
                for ($i = 0; $i < 100; $i++) {
                    $id = "users/" . $i;
                    $ids[$i] = $id;
                    $session->store(new User(), $id);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $re = $store->getRequestExecutor();
            $command = GetDocumentsCommand::forMultipleDocuments($ids, null, false);
            $re->execute($command);

            $this->assertCount(101,$command->getResult()->getResults());
            $this->assertTrue($command->getResult()->getResults()[count($command->getResult()->getResults()) - 1] == null);

        } finally {
            $store->close();
        }
    }

    public function testGetDocumentsCommandShouldDiscardNullIds_PostGet(): void
    {
        $store = $this->getDocumentStore();
        try {
            $ids = array_fill(0, 1024, null);;

            $session = $store->openSession();
            try {
                for ($i = 0; $i < 1000; $i++) {
                    $id = "users/" . $i;
                    $ids[$i] = $id;
                    $session->store(new User(), $id);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $re = $store->getRequestExecutor();
            $command = GetDocumentsCommand::forMultipleDocuments($ids, null, false);
            $re->execute($command);

            $this->assertCount(1001,$command->getResult()->getResults());
            $this->assertTrue($command->getResult()->getResults()[count($command->getResult()->getResults()) - 1] == null);
        } finally {
            $store->close();
        }
    }
}
