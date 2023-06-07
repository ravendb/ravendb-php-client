<?php

namespace tests\RavenDB\Test\Server\Etl\Raven;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\ConnectionStrings\PutConnectionStringOperation;
use RavenDB\Documents\Operations\ConnectionStrings\PutConnectionStringResult;
use RavenDB\Documents\Operations\Etl\AddEtlOperation;
use RavenDB\Documents\Operations\Etl\RavenConnectionString;
use RavenDB\Documents\Operations\Etl\RavenEtlConfiguration;
use RavenDB\Documents\Operations\Etl\ResetEtlOperation;
use RavenDB\Documents\Operations\Etl\Transformation;
use RavenDB\Documents\Operations\Etl\UpdateEtlOperation;
use RavenDB\Documents\Operations\GetOngoingTaskInfoOperation;
use RavenDB\Documents\Operations\OngoingTasks\DeleteOngoingTaskOperation;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskState;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskType;
use tests\RavenDB\Infrastructure\DisableOnPullRequestCondition;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\ReplicationTestBase;

class EtlTest extends ReplicationTestBase
{
    public function setUp(): void
    {
        parent::setUp();

        DisableOnPullRequestCondition::evaluateExecutionCondition($this);
    }

    public function testCanAddEtl(): void
    {
        $src = $this->getDocumentStore();
        try {
            $dst = $this->getDocumentStore();
            try {
                $this->insertDocument($src);
                $result = $this->createConnectionString($src, $dst);

                $this->assertNotNull($result);

                $etlConfiguration = new RavenEtlConfiguration();
                $etlConfiguration->setConnectionStringName("toDst");
                $etlConfiguration->setDisabled(false);
                $etlConfiguration->setName("etlToDst");
                $transformation = new Transformation();
                $transformation->setApplyToAllDocuments(true);
                $transformation->setName("Script #1");

                $etlConfiguration->setTransforms([$transformation]);

                $operation = new AddEtlOperation($etlConfiguration);
                $etlResult = $src->maintenance()->send($operation);

                $this->assertNotNull($etlResult);

                $this->assertGreaterThan(0, $etlResult->getRaftCommandIndex());
                $this->assertGreaterThan(0, $etlResult->getTaskId());

                $this->waitForDocumentToReplicate($dst, User::class, "users/1", 10* 1000);

                $ongoingTask = $src->maintenance()
                        ->send(new GetOngoingTaskInfoOperation($etlResult->getTaskId(), OngoingTaskType::ravenEtl()));

                $this->assertNotNull($ongoingTask);

                $this->assertEquals($etlResult->getTaskId(), $ongoingTask->getTaskId());
                $this->assertEquals(OngoingTaskType::ravenEtl(), $ongoingTask->getTaskType());
                $this->assertNotNull($ongoingTask->getResponsibleNode());
                $this->assertEquals(OngoingTaskState::enabled(), $ongoingTask->getTaskState());
                $this->assertEquals("etlToDst", $ongoingTask->getTaskName());

                $deleteResult = $src->maintenance()
                        ->send(new DeleteOngoingTaskOperation($etlResult->getTaskId(), OngoingTaskType::ravenEtl()));

                $this->assertEquals($etlResult->getTaskId(), $deleteResult->getTaskId());
            } finally {
                $dst->close();
            }
        } finally {
            $src->close();
        }
    }

    public function testCanAddEtlWithScript(): void
    {
        $src = $this->getDocumentStore();
        try {
            $dst = $this->getDocumentStore();
            try {
                $this->insertDocument($src);
                $result = $this->createConnectionString($src, $dst);

                $this->assertNotNull($result);

                $etlConfiguration = new RavenEtlConfiguration();
                $etlConfiguration->setConnectionStringName("toDst");
                $etlConfiguration->setDisabled(false);
                $etlConfiguration->setName("etlToDst");
                $transformation = new Transformation();
                $transformation->setApplyToAllDocuments(false);
                $transformation->setCollections([ "Users" ]);
                $transformation->setName("Script #1");
                $transformation->setScript("loadToUsers(this);");

                $etlConfiguration->setTransforms([ $transformation ]);

                $operation = new AddEtlOperation($etlConfiguration);
                $etlResult = $src->maintenance()->send($operation);

                $this->assertNotNull($etlResult);

                $this->assertGreaterThan(0, $etlResult->getRaftCommandIndex());

                $this->assertGreaterThan(0, $etlResult->getTaskId());

                $this->waitForDocumentToReplicate($dst, User::class, "users/1", 10* 1000);
            } finally {
                $dst->close();
            }
        } finally {
            $src->close();
        }
    }

    public function testCanUpdateEtl(): void
    {
        $src = $this->getDocumentStore();
        try {
            $dst = $this->getDocumentStore();
            try {
                $this->insertDocument($src);
                $result = $this->createConnectionString($src, $dst);

                $this->assertNotNull($result);

                $etlConfiguration = new RavenEtlConfiguration();
                $etlConfiguration->setConnectionStringName("toDst");
                $etlConfiguration->setDisabled(false);
                $etlConfiguration->setName("etlToDst");
                $transformation = new Transformation();
                $transformation->setApplyToAllDocuments(false);
                $transformation->setCollections([ "Users" ]);
                $transformation->setName("Script #1");
                $transformation->setScript("loadToUsers(this);");

                $etlConfiguration->setTransforms([ $transformation ]);

                $operation = new AddEtlOperation($etlConfiguration);
                $etlResult = $src->maintenance()->send($operation);

                $this->waitForDocumentToReplicate($dst, User::class, "users/1", 10* 1000);

                // now change ETL configuration

                $transformation->setCollections([ "Cars" ]);
                $transformation->setScript("loadToCars(this)");

                $updateResult = $src->maintenance()->send(new UpdateEtlOperation($etlResult->getTaskId(), $etlConfiguration));

                $this->assertNotNull($updateResult);

                $this->assertGreaterThan(0, $updateResult->getRaftCommandIndex());

                $this->assertGreaterThan($etlResult->getTaskId(), $updateResult->getTaskId());

                // this document shouldn't be replicated via ETL
                $session = $src->openSession();
                try {
                    $user1 = new User();
                    $user1->setName("John");
                    $session->store($user1, "users/2");
                    $session->saveChanges();
                } finally {
                    $session->close();
                }

                $this->waitForDocumentToReplicate($dst, User::class, "users/2", 4000);
            } finally {
                $dst->close();
            }
        } finally {
            $src->close();
        }
    }

    public function testCanResetEtlTask(): void
    {
        $src = $this->getDocumentStore();
        try {
            $dst = $this->getDocumentStore();
            try {
                $this->insertDocument($src);
                $result = $this->createConnectionString($src, $dst);

                $this->assertNotNull($result);

                $etlConfiguration = new RavenEtlConfiguration();
                $etlConfiguration->setConnectionStringName("toDst");
                $etlConfiguration->setDisabled(false);
                $etlConfiguration->setName("etlToDst");
                $transformation = new Transformation();
                $transformation->setApplyToAllDocuments(true);
                $transformation->setName("Script Q&A");

                $etlConfiguration->setTransforms([ $transformation ]);

                $operation = new AddEtlOperation($etlConfiguration);
                $etlResult = $src->maintenance()->send($operation);

                $this->assertNotNull($etlResult);

                $this->assertGreaterThan(0, $etlResult->getRaftCommandIndex());

                $this->assertGreaterThan(0, $etlResult->getTaskId());

                $this->waitForDocumentToReplicate($dst, User::class, "users/1", 10* 1000);

                $session = $dst->openSession();
                try {
                    $session->delete("users/1");
                } finally {
                    $session->close();
                }

                $src->maintenance()->send(new ResetEtlOperation("etlToDst", "Script Q&A"));

                // etl was reset - waiting again for users/1 doc
                $this->waitForDocumentToReplicate($dst, User::class, "users/1", 10* 1000);
            } finally {
                $dst->close();
            }
        } finally {
            $src->close();
        }
    }

    private function createConnectionString(DocumentStoreInterface $src, DocumentStoreInterface $dst): PutConnectionStringResult
    {
        $toDstLink = new RavenConnectionString();
        $toDstLink->setDatabase($dst->getDatabase());
        $toDstLink->setTopologyDiscoveryUrls($dst->getUrls());
        $toDstLink->setName("toDst");

        return $src->maintenance()->send(new PutConnectionStringOperation($toDstLink));
    }

    private function insertDocument(DocumentStoreInterface $src): void
    {
        $session = $src->openSession();
        try {
            $user1 = new User();
            $user1->setName("Marcin");
            $session->store($user1, "users/1");
            $session->saveChanges();
        } finally {
            $session->close();
        }
    }
}
