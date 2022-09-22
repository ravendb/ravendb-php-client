<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Commands\GetDocumentsCommand;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_14919Test extends RemoteTestBase
{
//    public function getCountersOperationShouldDiscardNullCounters(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            String docId = "users/2";
//
//            String[] counterNames = new String[124];
//
//            try (IDocumentSession session = store.openSession()) {
//                session.store(new User(), docId);
//
//                ISessionDocumentCounters c = session.countersFor(docId);
//                for (int i = 0; i < 100; i++) {
//                    String name = "likes" + i;
//                    counterNames[i] = name;
//                    c.increment(name);
//                }
//
//                session.saveChanges();
//            }
//
//            CountersDetail vals = store.operations().send(new GetCountersOperation(docId, counterNames));
//            assertThat(vals.getCounters())
//                    .hasSize(101);
//
//            for (int i = 0; i < 100; i++) {
//                assertThat(vals.getCounters().get(i).getTotalValue())
//                        .isEqualTo(1);
//            }
//
//            assertThat(vals.getCounters().get(vals.getCounters().size() - 1))
//                    .isNull();
//
//            // test with returnFullResults = true
//            vals = store.operations().send(new GetCountersOperation(docId, counterNames, true));
//
//            assertThat(vals.getCounters())
//                    .hasSize(101);
//
//            for (int i = 0; i < 100; i++) {
//                assertThat(vals.getCounters().get(i).getCounterValues())
//                        .hasSize(1);
//            }
//
//            assertThat(vals.getCounters().get(vals.getCounters().size() - 1))
//                    .isNull();
//        }
//    }
//
//    @Test
//    public function getCountersOperationShouldDiscardNullCounters_PostGet(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            String docId = "users/2";
//            String[] counterNames = new String[1024];
//
//            try (IDocumentSession session = store.openSession()) {
//                session.store(new User(), docId);
//
//                ISessionDocumentCounters c = session.countersFor(docId);
//
//                for (int i = 0; i < 1000; i++) {
//                    String name = "likes" + i;
//                    counterNames[i] = name;
//                    c.increment(name, i);
//                }
//
//                session.saveChanges();
//            }
//
//            CountersDetail vals = store.operations().send(new GetCountersOperation(docId, counterNames));
//            assertThat(vals.getCounters())
//                    .hasSize(1001);
//
//            for (int i = 0; i < 1000; i++) {
//                assertThat(vals.getCounters().get(i).getTotalValue())
//                        .isEqualTo(i);
//            }
//
//            assertThat(vals.getCounters().get(vals.getCounters().size() - 1))
//                    .isNull();
//
//            // test with returnFullResults = true
//            vals = store.operations().send(new GetCountersOperation(docId, counterNames, true));
//            assertThat(vals.getCounters())
//                    .hasSize(1001);
//
//            for (int i = 0; i < 1000; i++) {
//                assertThat(vals.getCounters().get(i).getTotalValue())
//                        .isEqualTo(i);
//            }
//
//            assertThat(vals.getCounters().get(vals.getCounters().size() - 1))
//                    .isNull();
//        }
//    }

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
