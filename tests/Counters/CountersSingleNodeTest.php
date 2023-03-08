<?php

namespace tests\RavenDB\Counters;

use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Operations\Counters\CounterBatch;
use RavenDB\Documents\Operations\Counters\CounterBatchOperation;
use RavenDB\Documents\Operations\Counters\CounterOperation;
use RavenDB\Documents\Operations\Counters\CounterOperationType;
use RavenDB\Documents\Operations\Counters\DocumentCountersOperation;
use RavenDB\Documents\Operations\Counters\GetCountersOperation;
use RavenDB\Utils\StringUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class CountersSingleNodeTest extends RemoteTestBase
{
      public function testIncrementCounter(): void
      {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $documentCountersOperation = new DocumentCountersOperation();
            $documentCountersOperation->setDocumentId("users/1-A");
            $documentCountersOperation->setOperations([CounterOperation::create("likes", CounterOperationType::increment(), 0)]);

            $counterBatch = new CounterBatch();
            $counterBatch->setDocuments([$documentCountersOperation]);

            $store->operations()->send(new CounterBatchOperation($counterBatch));


            $details = $store->operations()->send(new GetCountersOperation("users/1-A", [ "likes" ]));
            $val = $details->getCounters()[0]->getTotalValue();

            $this->assertEquals(0, $val);

            $documentCountersOperation = new DocumentCountersOperation();
            $documentCountersOperation->setDocumentId("users/1-A");
            $documentCountersOperation->setOperations([CounterOperation::create("likes", CounterOperationType::increment(), 10)]);

            $counterBatch = new CounterBatch();
            $counterBatch->setDocuments([$documentCountersOperation]);

            $store->operations()->send(new CounterBatchOperation($counterBatch));

            $details = $store->operations()->send(new GetCountersOperation("users/1-A", [ "likes" ]));
            $val = $details->getCounters()[0]->getTotalValue();

            $this->assertEquals(10, $val);

            $documentCountersOperation = new DocumentCountersOperation();
            $documentCountersOperation->setDocumentId("users/1-A");
            $documentCountersOperation->setOperations([CounterOperation::create("likes", CounterOperationType::increment(), -3)]);

            $counterBatch = new CounterBatch();
            $counterBatch->setDocuments([$documentCountersOperation]);

            $store->operations()->send(new CounterBatchOperation($counterBatch));

            $details = $store->operations()->send(new GetCountersOperation("users/1-A", [ "likes" ]));
            $val = $details->getCounters()[0]->getTotalValue();

            $this->assertEquals(7, $val);
        } finally {
            $store->close();
        }
    }


    public function testGetCounterValueUsingPOST(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $longCounterName = StringUtils::repeat('a', 500);

            $documentCountersOperation = new DocumentCountersOperation();
            $documentCountersOperation->setDocumentId("users/1-A");
            $documentCountersOperation->setOperations([CounterOperation::create($longCounterName, CounterOperationType::increment(), 5)]);

            $counterBatch = new CounterBatch();
            $counterBatch->setDocuments([$documentCountersOperation]);

            $store->operations()->send(new CounterBatchOperation($counterBatch));

            $session = $store->openSession();
            try {
                $dic = $session->countersFor("users/1-A")->get([$longCounterName, "no_such"]);
                $this->assertCount(1, $dic);
                $this->assertArrayHasKey($longCounterName, $dic);
                $this->assertEquals(5, $dic[$longCounterName]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testGetCounterValue(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $documentCountersOperation = new DocumentCountersOperation();
            $documentCountersOperation->setDocumentId("users/1-A");
            $documentCountersOperation->setOperations([CounterOperation::create("likes", CounterOperationType::increment(), 5)]);

            $counterBatch = new CounterBatch();
            $counterBatch->setDocuments([$documentCountersOperation]);

            $a = $store->operations()->send(new CounterBatchOperation($counterBatch));

            $documentCountersOperation = new DocumentCountersOperation();
            $documentCountersOperation->setDocumentId("users/1-A");
            $documentCountersOperation->setOperations([CounterOperation::create("likes", CounterOperationType::increment(), 10)]);

            $counterBatch = new CounterBatch();
            $counterBatch->setDocuments([$documentCountersOperation]);

            $b = $store->operations()->send(new CounterBatchOperation($counterBatch));

            $details = $store->operations()->send(new GetCountersOperation("users/1-A", [ "likes" ]));
            $val = $details->getCounters()[0]->getTotalValue();

            $this->assertEquals(15, $val);
        } finally {
            $store->close();
        }
    }

    public function testDeleteCounter(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Aviv1");

                $user2 = new User();
                $user2->setName("Aviv2");

                $session->store($user1, "users/1-A");
                $session->store($user2, "users/2-A");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([CounterOperation::create("likes", CounterOperationType::increment(), 10)]);

            $documentCountersOperation2 = new DocumentCountersOperation();
            $documentCountersOperation2->setDocumentId("users/2-A");
            $documentCountersOperation2->setOperations([CounterOperation::create("likes", CounterOperationType::increment(), 20)]);

            $counterBatch = new CounterBatch();
            $counterBatch->setDocuments([$documentCountersOperation1, $documentCountersOperation2]);

            $store->operations()->send(new CounterBatchOperation($counterBatch));

            $deleteCounter = new DocumentCountersOperation();
            $deleteCounter->setDocumentId("users/1-A");
            $deleteCounter->setOperations([CounterOperation::create("likes", CounterOperationType::delete())]);

            $counterBatch = new CounterBatch();
            $counterBatch->setDocuments([$deleteCounter]);

            $store->operations()->send(new CounterBatchOperation($counterBatch));

            $countersDetail = $store->operations()->send(new GetCountersOperation("users/1-A", [ "likes" ]));
            $this->assertCount(1, $countersDetail->getCounters());

            $this->assertNull($countersDetail->getCounters()[0]);

            $deleteCounter = new DocumentCountersOperation();
            $deleteCounter->setDocumentId("users/2-A");
            $deleteCounter->setOperations([CounterOperation::create("likes", CounterOperationType::delete())]);

            $counterBatch = new CounterBatch();
            $counterBatch->setDocuments([$deleteCounter]);

            $store->operations()->send(new CounterBatchOperation($counterBatch));

            $countersDetail = $store->operations()->send(new GetCountersOperation("users/2-A", ["likes"]));
            $this->assertCount(1, $countersDetail->getCounters());

            $this->assertNull($countersDetail->getCounters()[0]);
        } finally {
            $store->close();
        }
    }

    public function testMultiGet(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([
                    CounterOperation::create("likes", CounterOperationType::increment(), 5),
                    CounterOperation::create("dislikes", CounterOperationType::increment(), 10)
            ]);

            $counterBatch = new CounterBatch();
            $counterBatch->setDocuments([$documentCountersOperation1]);

            $store->operations()->send(new CounterBatchOperation($counterBatch));

            $counters = $store->operations()->send(new GetCountersOperation("users/1-A", ["likes", "dislikes"]))
                    ->getCounters();

            $this->assertCount(2, $counters);

            $likes = array_filter($counters->getArrayCopy(), function($x) { return $x->getCounterName() == 'likes';});
            $this->assertEquals(5, $likes[array_key_first($likes)]->getTotalValue());

            $dislikes = array_filter($counters->getArrayCopy(), function($x) { return $x->getCounterName() == 'dislikes';});
            $this->assertEquals(10, $dislikes[array_key_first($dislikes)]->getTotalValue());
        } finally {
            $store->close();
        }
    }

    public function testMultiSetAndGetViaBatch(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Aviv");

                $user2 = new User();
                $user2->setName("Aviv2");

                $session->store($user1, "users/1-A");
                $session->store($user2, "users/2-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([
                    CounterOperation::create("likes", CounterOperationType::increment(), 5),
                    CounterOperation::create("dislikes", CounterOperationType::increment(), 10)
            ]);

            $documentCountersOperation2 = new DocumentCountersOperation();
            $documentCountersOperation2->setDocumentId("users/2-A");
            $documentCountersOperation2->setOperations([
                    CounterOperation::create("rank", CounterOperationType::increment(), 20)
            ]);

            $setBatch = new CounterBatch();
            $setBatch->setDocuments([$documentCountersOperation1, $documentCountersOperation2]);

            $store->operations()->send(new CounterBatchOperation($setBatch));

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([
                    CounterOperation::create("likes", CounterOperationType::get()),
                    CounterOperation::create("dislikes", CounterOperationType::get())
            ]);

            $documentCountersOperation2 = new DocumentCountersOperation();
            $documentCountersOperation2->setDocumentId("users/2-A");
            $documentCountersOperation2->setOperations([
                    CounterOperation::create("rank", CounterOperationType::get())
            ]);

            $getBatch = new CounterBatch();
            $getBatch->setDocuments([$documentCountersOperation1, $documentCountersOperation2]);

            $countersDetail = $store->operations()->send(new CounterBatchOperation($getBatch));

            $this->assertCount(3, $countersDetail->getCounters());

            $this->assertEquals("likes", $countersDetail->getCounters()[0]->getCounterName());
            $this->assertEquals("users/1-A", $countersDetail->getCounters()[0]->getDocumentId());
            $this->assertEquals(5, $countersDetail->getCounters()[0]->getTotalValue());

            $this->assertEquals("dislikes", $countersDetail->getCounters()[1]->getCounterName());
            $this->assertEquals("users/1-A", $countersDetail->getCounters()[1]->getDocumentId());
            $this->assertEquals(10, $countersDetail->getCounters()[1]->getTotalValue());

            $this->assertEquals("rank", $countersDetail->getCounters()[2]->getCounterName());
            $this->assertEquals("users/2-A", $countersDetail->getCounters()[2]->getDocumentId());
            $this->assertEquals(20, $countersDetail->getCounters()[2]->getTotalValue());

        } finally {
            $store->close();
        }
    }

    public function testDeleteCreateWithSameNameDeleteAgain(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([
                    CounterOperation::create("likes", CounterOperationType::increment(), 10)
            ]);

            $batch = new CounterBatch();
            $batch->setDocuments([$documentCountersOperation1]);
            $store->operations()->send(new CounterBatchOperation($batch));

            $this->assertEquals(10,
                $store->operations()->send(new GetCountersOperation("users/1-A", ["likes"]))
                    ->getCounters()[0]
                    ->getTotalValue());

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([
                    CounterOperation::create("likes", CounterOperationType::delete())
            ]);

            $batch = new CounterBatch();
            $batch->setDocuments([$documentCountersOperation1]);
            $store->operations()->send(new CounterBatchOperation($batch));

            $countersDetail = $store->operations()->send(new GetCountersOperation("users/1-A", ["likes"]));
            $this->assertCount(1, $countersDetail->getCounters());
            $this->assertNull($countersDetail->getCounters()[0]);

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([
                    CounterOperation::create("likes", CounterOperationType::increment(), 20)
            ]);

            $batch = new CounterBatch();
            $batch->setDocuments([$documentCountersOperation1]);
            $store->operations()->send(new CounterBatchOperation($batch));

            $this->assertEquals(20,
                $store->operations()->send(new GetCountersOperation("users/1-A", ["likes"]))
                    ->getCounters()[0]
                    ->getTotalValue());

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([
                    CounterOperation::create("likes", CounterOperationType::delete())
            ]);

            $batch = new CounterBatch();
            $batch->setDocuments([$documentCountersOperation1]);
            $store->operations()->send(new CounterBatchOperation($batch));

            $countersDetail = $store->operations()->send(
                    new GetCountersOperation("users/1-A", [ "likes" ]));
            $this->assertCount(1, $countersDetail->getCounters());

            $this->assertNull($countersDetail->getCounters()[0]);
        } finally {
            $store->close();
        }
    }

    public function testIncrementAndDeleteShouldChangeDocumentMetadata(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");

                $session->store($user, "users/1-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([
                    CounterOperation::create("likes", CounterOperationType::increment(), 10)
            ]);

            $batch = new CounterBatch();
            $batch->setDocuments([$documentCountersOperation1]);
            $store->operations()->send(new CounterBatchOperation($batch));

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $metadata = $session->advanced()->getMetadataFor($user);

                $counters = $metadata->get(DocumentsMetadata::COUNTERS);
                $this->assertCount(1, $counters);
                $this->assertContains("likes", $counters);
            } finally {
                $session->close();
            }

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([
                    CounterOperation::create("votes", CounterOperationType::increment(), 50)
            ]);

            $batch = new CounterBatch();
            $batch->setDocuments([$documentCountersOperation1]);
            $store->operations()->send(new CounterBatchOperation($batch));

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $metadata = $session->advanced()->getMetadataFor($user);

                $counters = $metadata->get(DocumentsMetadata::COUNTERS);
                $this->assertCount(2, $counters);
                $this->assertContains("likes", $counters);
                $this->assertContains("votes", $counters);
            } finally {
                $session->close();
            }

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([
                    CounterOperation::create("likes", CounterOperationType::delete())
            ]);

            $batch = new CounterBatch();
            $batch->setDocuments([$documentCountersOperation1]);
            $store->operations()->send(new CounterBatchOperation($batch));

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $metadata = $session->advanced()->getMetadataFor($user);

                $counters = $metadata->get(DocumentsMetadata::COUNTERS);
                $this->assertCount(1, $counters);
                $this->assertContains("votes", $counters);
            } finally {
                $session->close();
            }

            $documentCountersOperation1 = new DocumentCountersOperation();
            $documentCountersOperation1->setDocumentId("users/1-A");
            $documentCountersOperation1->setOperations([
                    CounterOperation::create("votes", CounterOperationType::delete())
            ]);

            $batch = new CounterBatch();
            $batch->setDocuments([$documentCountersOperation1]);
            $store->operations()->send(new CounterBatchOperation($batch));

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $metadata = $session->advanced()->getMetadataFor($user);

                $counters = $metadata->get(DocumentsMetadata::COUNTERS);
                $this->assertNull($counters);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCounterNameShouldPreserveCase(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1-A");

                $session->countersFor("users/1-A")->increment("Likes", 10);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $val = $session->countersFor($user)->get("Likes");
                $this->assertEquals(10, $val);

                $counters = $session->advanced()->getCountersFor($user);
                $this->assertCount(1, $counters);
                $this->assertContains("Likes", $counters);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
