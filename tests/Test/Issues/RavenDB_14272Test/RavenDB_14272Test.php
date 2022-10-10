<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14272Test;

use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Queries\QueryData;
use tests\RavenDB\RemoteTestBase;

class RavenDB_14272Test extends RemoteTestBase
{
    public function testSelect_Fields1(): void
    {
        $store = $this->getDocumentStore();
        try {
            $userTalk = $this->saveUserTalk($store);

            $session = $store->openSession();
            try {
                /** @var array<TalkUserIds> $result */
                $result = $session->query(get_class($userTalk))
                        ->selectFields(TalkUserIds::class)
                        ->toList();

                $this->assertCount(1, $result);

                $this->assertCount(2, $result[0]->getUserDefs());

                foreach ($result[0]->getUserDefs() as $key => $userDef) {
                    $this->assertArrayHasKey($key, $userTalk->getUserDefs());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSelect_Fields2(): void
    {
        $store = $this->getDocumentStore();
        try {
            $userTalk = $this->saveUserTalk($store);

            $session = $store->openSession();
            try {
                /** @var array<TalkUserIds> $result */
                $result = $session->query(get_class($userTalk))
                    ->selectFields(TalkUserIds::class, "userDefs")
                    ->toList();

                $this->assertCount(1, $result);

                $this->assertCount(2, $result[0]->getUserDefs());

                foreach ($result[0]->getUserDefs() as $key => $userDef) {
                    $this->assertArrayHasKey($key, $userTalk->getUserDefs());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSelect_Fields3(): void
    {
        $store = $this->getDocumentStore();
        try {
            $userTalk = $this->saveUserTalk($store);

            $session = $store->openSession();
            try {
                $queryData = new QueryData(["userDefs"], ["userDefs"]);

                /** @var array<TalkUserIds> $result */
                $result = $session->query(get_class($userTalk))
                    ->selectFields(TalkUserIds::class, $queryData)
                    ->toList();

                $this->assertCount(1, $result);

                $this->assertCount(2, $result[0]->getUserDefs());

                foreach ($result[0]->getUserDefs() as $key => $userDef) {
                    $this->assertArrayHasKey($key, $userTalk->getUserDefs());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSelect_Fields4(): void
    {
        $store = $this->getDocumentStore();
        try {
            $userTalk = $this->saveUserTalk($store);

            $session = $store->openSession();
            try {
                /** @var array<TalkUserIds> $result */
                $result = $session->query(get_class($userTalk))
                    ->selectFields(null, "name")
                    ->toList();

                $this->assertCount(1, $result);
                $this->assertEquals($userTalk->getName(), $result[0]);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

//    public function streaming_query_projection(): void {
//        try (DocumentStore store = getDocumentStore()) {
//            UserTalk userTalk = saveUserTalk(store);
//
//            try (IDocumentSession session = $store->openSession()) {
//                IDocumentQuery<TalkUserIds> query = $session->query($userTalk->class)
//                        .selectFields(TalkUserIds.class);
//
//                try (CloseableIterator<StreamResult<TalkUserIds>> stream = $session->advanced().stream(query)) {
//                    while (stream.hasNext()) {
//                        TalkUserIds projection = stream.next().getDocument();
//
//                        assertThat(projection)
//                                .isNotNull();
//                        assertThat(projection.getUserDefs())
//                                .isNotNull()
//                                .hasSize(2);
//
//                        assertThat($userTalk->getUserDefs().keySet())
//                                .contains(projection.getUserDefs().keySet().toArray(new String[0]));
//
//                    }
//                }
//            }
//        }
//    }
//
//    @Test
//    public function streaming_document_query_projection(): void {
//        try (DocumentStore store = getDocumentStore()) {
//            UserTalk userTalk = saveUserTalk(store);
//
//            try (IDocumentSession session = $store->openSession()) {
//                IDocumentQuery<TalkUserIds> query = $session->advanced().documentQuery($userTalk->class)
//                        .selectFields(TalkUserIds.class, "userDefs");
//                try (CloseableIterator<StreamResult<TalkUserIds>> stream = $session->advanced().stream(query)) {
//                    while (stream.hasNext()) {
//                        TalkUserIds projection = stream.next().getDocument();
//                        assertThat(projection)
//                                .isNotNull();
//                        assertThat(projection.getUserDefs())
//                                .isNotNull()
//                                .hasSize(2);
//                        assertThat($userTalk->getUserDefs().keySet())
//                                .contains(projection.getUserDefs().keySet().toArray(new String[0]));
//                    }
//                }
//            }
//        }
//    }

    private function saveUserTalk(DocumentStore $store): UserTalk
    {
        $userTalk = new UserTalk();

        $userDefs = new TalkUserDefArray();
        $userDefs->offsetSet("test1", new TalkUserDef());
        $userDefs->offsetSet("test2", new TalkUserDef());

        $userTalk->setUserDefs($userDefs);
        $userTalk->setName("Grisha");

        $session = $store->openSession();
        try {
            $session->store($userTalk);
            $session->saveChanges();
        } finally {
            $session->close();
        }

        return $userTalk;
    }
}
