<?php

namespace tests\RavenDB\Test\Client\Indexing;

use RavenDB\Documents\Indexes\AbstractIndexCreationTaskArray;
use RavenDB\Documents\Indexes\IndexCreation;
use RavenDB\Documents\Operations\Indexes\GetTermsOperation;
use RavenDB\Documents\Session\QueryStatistics;
use RavenDB\Type\StringArray;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\Indexing\Index\Users_ByName;

class IndexesFromClientTest extends RemoteTestBase
{
    public function testCanCreateIndexesUsingIndexCreation(): void
    {
        $store = $this->getDocumentStore();
        try {
            IndexCreation::createIndexes(AbstractIndexCreationTaskArray::fromArray([new Users_ByName()]), $store);

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Marcin");
                $session->store($user1, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $users = $session->query(User::class, Users_ByName::class)
                        ->toList();

                $this->assertCount(1, $users);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

//    @Test
//    public void canReset() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                User user1 = new User();
//                user1.setName("Marcin");
//                session.store(user1, "users/1");
//                session.saveChanges();
//            }
//
//            store.executeIndex(new UsersIndex());
//
//            waitForIndexing(store);
//
//            GetStatisticsOperation.GetStatisticsCommand command = new GetStatisticsOperation.GetStatisticsCommand();
//            store.getRequestExecutor().execute(command);
//
//            DatabaseStatistics statistics = command.getResult();
//            Date firstIndexingTime = statistics.getIndexes()[0].getLastIndexingTime();
//
//            String indexName = new UsersIndex().getIndexName();
//
//            // now reset index
//
//            Thread.sleep(2); /// avoid the same millisecond
//
//            store.maintenance().send(new ResetIndexOperation(indexName));
//            waitForIndexing(store);
//
//            command = new GetStatisticsOperation.GetStatisticsCommand();
//            store.getRequestExecutor().execute(command);
//
//            statistics = command.getResult();
//
//            Date secondIndexingTime = statistics.getLastIndexingTime();
//            assertThat(firstIndexingTime)
//                    .isBefore(secondIndexingTime);
//        }
//    }
//
//    @Test
//    public void canExecuteManyIndexes() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndexes(Collections.singletonList(new UsersIndex()));
//
//            GetIndexNamesOperation indexNamesOperation = new GetIndexNamesOperation(0, 10);
//            String[] indexNames = store.maintenance().send(indexNamesOperation);
//
//            assertThat(indexNames)
//                    .hasSize(1);
//        }
//    }
//
//    public static class UsersIndex extends AbstractIndexCreationTask {
//        public UsersIndex() {
//            map = "from user in docs.users select new { user.name }";
//        }
//    }
//
//    @Test
//    public void canDelete() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndex(new UsersIndex());
//
//            store.maintenance().send(new DeleteIndexOperation(new UsersIndex().getIndexName()));
//
//            GetStatisticsOperation.GetStatisticsCommand command = new GetStatisticsOperation.GetStatisticsCommand();
//            store.getRequestExecutor().execute(command);
//
//            DatabaseStatistics statistics = command.getResult();
//
//            assertThat(statistics.getIndexes())
//                    .hasSize(0);
//        }
//    }
//
//    @Test
//    public void canStopAndStart() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            new Users_ByName().execute(store);
//
//            IndexingStatus status = store.maintenance().send(new GetIndexingStatusOperation());
//
//            assertThat(status.getStatus())
//                    .isEqualTo(IndexRunningStatus.RUNNING);
//
//            assertThat(status.getIndexes())
//                    .hasSize(1);
//
//            assertThat(status.getIndexes()[0].getStatus())
//                    .isEqualTo(IndexRunningStatus.RUNNING);
//
//            store.maintenance().send(new StopIndexingOperation());
//
//            status = store.maintenance().send(new GetIndexingStatusOperation());
//
//            assertThat(status.getStatus())
//                    .isEqualTo(IndexRunningStatus.PAUSED);
//
//            assertThat(status.getIndexes()[0].getStatus())
//                    .isEqualTo(IndexRunningStatus.PAUSED);
//
//            store.maintenance().send(new StartIndexingOperation());
//
//            status = store.maintenance().send(new GetIndexingStatusOperation());
//
//            assertThat(status.getStatus())
//                    .isEqualTo(IndexRunningStatus.RUNNING);
//
//            assertThat(status.getIndexes())
//                    .hasSize(1);
//
//            assertThat(status.getIndexes()[0].getStatus())
//                    .isEqualTo(IndexRunningStatus.RUNNING);
//
//            store.maintenance().send(new StopIndexOperation(status.getIndexes()[0].getName()));
//
//            status = store.maintenance().send(new GetIndexingStatusOperation());
//
//            assertThat(status.getStatus())
//                    .isEqualTo(IndexRunningStatus.RUNNING);
//
//            assertThat(status.getIndexes())
//                    .hasSize(1);
//
//            assertThat(status.getIndexes()[0].getStatus())
//                    .isEqualTo(IndexRunningStatus.PAUSED);
//        }
//    }

//    public void setLockModeAndSetPriority() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//
//            new Users_ByName().execute(store);
//
//            try (IDocumentSession session = store.openSession()) {
//                User user1 = new User();
//                user1.setName("Fitzchak");
//                session.store(user1);
//
//                User user2 = new User();
//                user2.setName("Arek");
//                session.store(user2);
//
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                List<User> users = session
//                        .query(User.class, Users_ByName.class)
//                        .waitForNonStaleResults()
//                        .whereEquals("name", "Arek")
//                        .toList();
//
//
//                assertThat(users)
//                        .hasSize(1);
//            }
//
//            IndexDefinition[] indexes = store.maintenance().send(new GetIndexesOperation(0, 128));
//            assertThat(indexes)
//                    .hasSize(1);
//
//            IndexDefinition index = indexes[0];
//            IndexStats stats = store.maintenance().send(new GetIndexStatisticsOperation(index.getName()));
//
//            assertThat(stats.getLockMode())
//                    .isEqualTo(IndexLockMode.UNLOCK);
//            assertThat(stats.getPriority())
//                    .isEqualTo(IndexPriority.NORMAL);
//
//            store.maintenance().send(new SetIndexesLockOperation(index.getName(), IndexLockMode.LOCKED_IGNORE));
//            store.maintenance().send(new SetIndexesPriorityOperation(index.getName(), IndexPriority.LOW));
//
//            stats = store.maintenance().send(new GetIndexStatisticsOperation(index.getName()));
//
//            assertThat(stats.getLockMode())
//                    .isEqualTo(IndexLockMode.LOCKED_IGNORE);
//            assertThat(stats.getPriority())
//                    .isEqualTo(IndexPriority.LOW);
//        }
//    }

    public function testGetTerms(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Fitzchak");
                $session->store($user1);

                $user2 = new User();
                $user2->setName("Arek");
                $session->store($user2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $indexName = null;

            $session = $store->openSession();
            try {
                $stats = new QueryStatistics();
                $users = $session
                    ->query(User::class)
                    ->waitForNonStaleResults()
                    ->statistics($stats)
                    ->whereEquals("name", "Arek")
                    ->toList();

                $indexName = $stats->getIndexName();
            } finally {
                $session->close();
            }

            /** @var StringArray $terms */
            $terms = $store->maintenance()->send(new GetTermsOperation($indexName, "name", null, 128));

            $this->assertCount(2, $terms);
            $this->assertContains("fitzchak", $terms);
            $this->assertContains("arek", $terms);
        } finally {
            $store->close();
        }
    }

//    @Test
//    public void getIndexNames() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                User user1 = new User();
//                user1.setName("Fitzchak");
//                session.store(user1);
//
//                User user2 = new User();
//                user2.setName("Arek");
//                session.store(user2);
//
//                session.saveChanges();
//            }
//
//            String indexName;
//
//            try (IDocumentSession session = store.openSession()) {
//                Reference<QueryStatistics> statsRef = new Reference<>();
//                List<User> users = session
//                        .query(User.class)
//                        .waitForNonStaleResults()
//                        .statistics(statsRef)
//                        .whereEquals("name", "Arek")
//                        .toList();
//
//                indexName = statsRef.value.getIndexName();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                String[] indexNames = store.maintenance().send(new GetIndexNamesOperation(0, 10));
//
//                assertThat(indexNames)
//                        .hasSize(1);
//
//                assertThat(indexNames)
//                        .contains(indexName);
//            }
//        }
//    }
//
//    @SuppressWarnings("UnusedAssignment")
//    @Test
//    public void canExplain() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            User user1 = new User();
//            user1.setName("Fitzchak");
//
//            User user2 = new User();
//            user2.setName("Arek");
//
//            try (IDocumentSession session = store.openSession()) {
//                session.store(user1);
//                session.store(user2);
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                Reference<QueryStatistics> statsRef = new Reference<>();
//                List<User> users = session.query(User.class)
//                        .statistics(statsRef)
//                        .whereEquals("name", "Arek")
//                        .toList();
//
//                users = session.query(User.class)
//                        .statistics(statsRef)
//                        .whereGreaterThan("age", 10)
//                        .toList();
//            }
//
//            IndexQuery indexQuery = new IndexQuery("from users");
//            ExplainQueryCommand command = new ExplainQueryCommand(store.getConventions(), indexQuery);
//
//            store.getRequestExecutor().execute(command);
//
//            ExplainQueryCommand.ExplainQueryResult[] explanations = command.getResult();
//            assertThat(explanations)
//                    .hasSize(1);
//            assertThat(explanations[0].getIndex())
//                    .isNotNull();
//            assertThat(explanations[0].getReason())
//                    .isNotNull();
//        }
//    }
//
//    @Test
//    public void moreLikeThis() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                Post post1 = new Post();
//                post1.setId("posts/1");
//                post1.setTitle("doduck");
//                post1.setDesc("prototype");
//                session.store(post1);
//
//                Post post2 = new Post();
//                post2.setId("posts/2");
//                post2.setTitle("doduck");
//                post2.setDesc("prototype your idea");
//                session.store(post2);
//
//                Post post3 = new Post();
//                post3.setId("posts/3");
//                post3.setTitle("doduck");
//                post3.setDesc("love programming");
//                session.store(post3);
//
//                Post post4 = new Post();
//                post4.setId("posts/4");
//                post4.setTitle("We do");
//                post4.setDesc("prototype");
//                session.store(post4);
//
//                Post post5 = new Post();
//                post5.setId("posts/5");
//                post5.setTitle("We love");
//                post5.setDesc("challange");
//                session.store(post5);
//
//                session.saveChanges();
//            }
//
//            new Posts_ByTitleAndDesc().execute(store);
//            waitForIndexing(store);
//
//            try (IDocumentSession session = store.openSession()) {
//
//                MoreLikeThisOptions options = new MoreLikeThisOptions();
//                options.setMinimumDocumentFrequency(1);
//                options.setMinimumTermFrequency(0);
//
//                List<Post> list = session.query(Post.class, Posts_ByTitleAndDesc.class)
//                        .moreLikeThis(f -> f.usingDocument(x -> x.whereEquals("id()", "posts/1")).withOptions(options))
//                        .toList();
//
//                assertThat(list)
//                        .hasSize(3);
//
//                assertThat(list.get(0).getTitle())
//                        .isEqualTo("doduck");
//                assertThat(list.get(0).getDesc())
//                        .isEqualTo("prototype your idea");
//
//                assertThat(list.get(1).getTitle())
//                        .isEqualTo("doduck");
//                assertThat(list.get(1).getDesc())
//                        .isEqualTo("love programming");
//
//                assertThat(list.get(2).getTitle())
//                        .isEqualTo("We do");
//                assertThat(list.get(2).getDesc())
//                        .isEqualTo("prototype");
//            }
//        }
//    }
}
