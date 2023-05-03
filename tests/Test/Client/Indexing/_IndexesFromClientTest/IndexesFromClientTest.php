<?php

namespace tests\RavenDB\Test\Client\Indexing\_IndexesFromClientTest;

use RavenDB\Documents\Commands\ExplainQueryCommand;
use RavenDB\Documents\Commands\ExplainQueryResultArray;
use RavenDB\Documents\Indexes\AbstractIndexCreationTaskArray;
use RavenDB\Documents\Indexes\IndexCreation;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Indexes\IndexDefinitionArray;
use RavenDB\Documents\Indexes\IndexingStatus;
use RavenDB\Documents\Indexes\IndexLockMode;
use RavenDB\Documents\Indexes\IndexPriority;
use RavenDB\Documents\Indexes\IndexStats;
use RavenDB\Documents\Operations\DatabaseStatistics;
use RavenDB\Documents\Operations\GetStatisticsCommand;
use RavenDB\Documents\Operations\Indexes\DeleteIndexOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexesOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexingStatusOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexNamesOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexStatisticsOperation;
use RavenDB\Documents\Operations\Indexes\GetTermsOperation;
use RavenDB\Documents\Operations\Indexes\ResetIndexOperation;
use RavenDB\Documents\Operations\Indexes\SetIndexesLockOperation;
use RavenDB\Documents\Operations\Indexes\SetIndexesPriorityOperation;
use RavenDB\Documents\Operations\Indexes\StartIndexingOperation;
use RavenDB\Documents\Operations\Indexes\StopIndexingOperation;
use RavenDB\Documents\Operations\Indexes\StopIndexOperation;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\MoreLikeThis\MoreLikeThisOptions;
use RavenDB\Documents\Session\QueryStatistics;
use RavenDB\Type\StringArray;
use tests\RavenDB\Infrastructure\Entity\Post;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

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

    public function testCanReset(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Marcin");
                $session->store($user1, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $store->executeIndex(new UsersIndex());

            $this->waitForIndexing($store);

            $command = new GetStatisticsCommand();
            $store->getRequestExecutor()->execute($command);

            /** @var DatabaseStatistics $statistics */
            $statistics = $command->getResult();
            $firstIndexingTime = $statistics->getIndexes()[0]->getLastIndexingTime();

            $indexName = (new UsersIndex())->getIndexName();

            // now reset index

            usleep(2000); // avoid the same millisecond

            $store->maintenance()->send(new ResetIndexOperation($indexName));
            $this->waitForIndexing($store);

            $command = new GetStatisticsCommand();
            $store->getRequestExecutor()->execute($command);

            /** @var DatabaseStatistics $statistics */
            $statistics = $command->getResult();

            $secondIndexingTime = $statistics->getLastIndexingTime();

            $this->assertTrue($firstIndexingTime < $secondIndexingTime);
        } finally {
            $store->close();
        }
    }

    public function testCanExecuteManyIndexes(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndexes([new UsersIndex()]);

            $indexNamesOperation = new GetIndexNamesOperation(0, 10);
            $indexNames = $store->maintenance()->send($indexNamesOperation);

            $this->assertCount(1, $indexNames);
        } finally {
            $store->close();
        }
    }

    public function testCanDelete(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new UsersIndex());

            $store->maintenance()->send(new DeleteIndexOperation((new UsersIndex())->getIndexName()));

            $command = new GetStatisticsCommand();
            $store->getRequestExecutor()->execute($command);

            /** @var DatabaseStatistics $statistics */
            $statistics = $command->getResult();

            $this->assertEmpty($statistics->getIndexes());
        } finally {
            $store->close();
        }
    }

    public function testCanStopAndStart(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new Users_ByName())->execute($store);

            /** @var IndexingStatus $status */
            $status = $store->maintenance()->send(new GetIndexingStatusOperation());

            $this->assertTrue($status->getStatus()->isRunning());

            $this->assertCount(1, $status->getIndexes());

            $this->assertTrue($status->getIndexes()[0]->getStatus()->isRunning());

            $store->maintenance()->send(new StopIndexingOperation());

            /** @var IndexingStatus $status */
            $status = $store->maintenance()->send(new GetIndexingStatusOperation());

            $this->assertTrue($status->getStatus()->isPaused());

            $this->assertTrue($status->getIndexes()[0]->getStatus()->isPaused());

            $store->maintenance()->send(new StartIndexingOperation());

            /** @var IndexingStatus $status */
            $status = $store->maintenance()->send(new GetIndexingStatusOperation());

            $this->assertTrue($status->getStatus()->isRunning());

            $this->assertCount(1, $status->getIndexes());

            $this->assertTrue($status->getIndexes()[0]->getStatus()->isRunning());

            $store->maintenance()->send(new StopIndexOperation($status->getIndexes()[0]->getName()));

            /** @var IndexingStatus $status */
            $status = $store->maintenance()->send(new GetIndexingStatusOperation());

            $this->assertTrue($status->getStatus()->isRunning());

            $this->assertCount(1, $status->getIndexes());

            $this->assertTrue($status->getIndexes()[0]->getStatus()->isPaused());
        } finally {
            $store->close();
        }
    }

    public function testSetLockModeAndSetPriority(): void
    {
        $store = $this->getDocumentStore();
        try {

            (new Users_ByName())->execute($store);

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

            $session = $store->openSession();
            try {
                $users = $session
                        ->query(User::class, Users_ByName::class)
                        ->waitForNonStaleResults()
                        ->whereEquals("name", "Arek")
                        ->toList();

                $this->assertCount(1, $users);
            } finally {
                $session->close();
            }

            /** @var IndexDefinitionArray $indexes */
            $indexes = $store->maintenance()->send(new GetIndexesOperation(0, 128));
            $this->assertCount(1, $indexes);

            /** @var IndexDefinition $index */
            $index = $indexes[0];
            /** @var IndexStats $stats */
            $stats = $store->maintenance()->send(new GetIndexStatisticsOperation($index->getName()));

            $this->assertTrue($stats->getLockMode()->isUnlock());
            $this->assertTrue($stats->getPriority()->isNormal());

            $store->maintenance()->send(new SetIndexesLockOperation($index->getName(), IndexLockMode::lockedIgnore()));
            $store->maintenance()->send(new SetIndexesPriorityOperation($index->getName(), IndexPriority::low()));

            /** @var IndexStats $stats */
            $stats = $store->maintenance()->send(new GetIndexStatisticsOperation($index->getName()));

            $this->assertTrue($stats->getLockMode()->isLockedIgnore());
            $this->assertTrue($stats->getPriority()->isLow());
        } finally {
            $store->close();
        }
    }

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

    public function testGetIndexNames(): void
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

            $indexName = '';

            $session = $store->openSession();
            try {
                $statsRef = new QueryStatistics();
                $users = $session
                    ->query(User::class)
                    ->waitForNonStaleResults()
                    ->statistics($statsRef)
                    ->whereEquals("name", "Arek")
                    ->toList();

                $indexName = $statsRef->getIndexName();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $indexNames = $store->maintenance()->send(new GetIndexNamesOperation(0, 10));

                $this->assertCount(1, $indexNames);
                $this->assertContains($indexName, $indexNames);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanExplain(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user1 = new User();
            $user1->setName("Fitzchak");

            $user2 = new User();
            $user2->setName("Arek");

            $session = $store->openSession();
            try {
                $session->store($user1);
                $session->store($user2);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $statsRef = new QueryStatistics();
                $users = $session->query(User::class)
                    ->statistics($statsRef)
                    ->whereEquals("name", "Arek")
                    ->toList();

                $users = $session->query(User::class)
                    ->statistics($statsRef)
                    ->whereGreaterThan("age", 10)
                    ->toList();
            } finally {
                $session->close();
            }

            $indexQuery = new IndexQuery("from users");
            $command = new ExplainQueryCommand($store->getConventions(), $indexQuery);

            $store->getRequestExecutor()->execute($command);

            /** @var ExplainQueryResultArray $explanations */
            $explanations = $command->getResult();
            $this->assertCount(1, $explanations);
            $this->assertNotNull($explanations[0]->getIndex());
            $this->assertNotNull($explanations[0]->getReason());
        } finally {
            $store->close();
        }
    }

    public function testMoreLikeThis(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $post1 = new Post();
                $post1->setId("posts/1");
                $post1->setTitle("doduck");
                $post1->setDesc("prototype");
                $session->store($post1);

                $post2 = new Post();
                $post2->setId("posts/2");
                $post2->setTitle("doduck");
                $post2->setDesc("prototype your idea");
                $session->store($post2);

                $post3 = new Post();
                $post3->setId("posts/3");
                $post3->setTitle("doduck");
                $post3->setDesc("love programming");
                $session->store($post3);

                $post4 = new Post();
                $post4->setId("posts/4");
                $post4->setTitle("We do");
                $post4->setDesc("prototype");
                $session->store($post4);

                $post5 = new Post();
                $post5->setId("posts/5");
                $post5->setTitle("We love");
                $post5->setDesc("challange");
                $session->store($post5);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            (new Posts_ByTitleAndDesc())->execute($store);
            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $options = new MoreLikeThisOptions();
                $options->setMinimumDocumentFrequency(1);
                $options->setMinimumTermFrequency(0);

                $list = $session->query(Post::class, Posts_ByTitleAndDesc::class)
                        ->moreLikeThis(
                            function($f) use ($options) {
                                return $f->usingDocument(
                                    function($x) { return $x->whereEquals("id()", "posts/1"); })
                                    ->withOptions($options);
                            })
                        ->toList();

                $this->assertCount(3, $list);

                $this->assertEquals("doduck", $list[0]->getTitle());
                $this->assertEquals("prototype your idea", $list[0]->getDesc());

                $this->assertEquals("doduck", $list[1]->getTitle());
                $this->assertEquals("love programming", $list[1]->getDesc());

                $this->assertEquals("We do", $list[2]->getTitle());
                $this->assertEquals("prototype", $list[2]->getDesc());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
