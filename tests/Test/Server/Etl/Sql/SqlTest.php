<?php

namespace tests\RavenDB\Test\Server\Etl\Sql;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\ConnectionStrings\PutConnectionStringOperation;
use RavenDB\Documents\Operations\ConnectionStrings\PutConnectionStringResult;
use RavenDB\Documents\Operations\Etl\AddEtlOperation;
use RavenDB\Documents\Operations\Etl\ResetEtlOperation;
use RavenDB\Documents\Operations\Etl\Sql\SqlConnectionString;
use RavenDB\Documents\Operations\Etl\Sql\SqlEtlConfiguration;
use RavenDB\Documents\Operations\Etl\Sql\SqlEtlTable;
use RavenDB\Documents\Operations\Etl\Transformation;
use RavenDB\Documents\Operations\Etl\UpdateEtlOperation;
use RavenDB\Documents\Operations\GetOngoingTaskInfoOperation;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskState;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskType;
use tests\RavenDB\Infrastructure\DisableOnPullRequestCondition;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\ReplicationTestBase;

class SqlTest extends ReplicationTestBase
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
            $this->insertDocument($src);
            $result = $this->createConnectionString($src);

            $this->assertNotNull($result);

            $etlConfiguration = new SqlEtlConfiguration();
            $etlConfiguration->setConnectionStringName("toDst");
            $etlConfiguration->setDisabled(false);
            $etlConfiguration->setName("etlToDst");
            $transformation = new Transformation();
            $transformation->setApplyToAllDocuments(true);
            $transformation->setName("Script #1");

            $table1 = new SqlEtlTable();
            $table1->setDocumentIdColumn("Id");
            $table1->setInsertOnlyMode(false);
            $table1->setTableName("Users");

            $etlConfiguration->setSqlTables([$table1]);
            $etlConfiguration->setTransforms([$transformation]);

            $operation = new AddEtlOperation($etlConfiguration);
            $etlResult = $src->maintenance()->send($operation);

            $this->assertNotNull($etlResult);

            $this->assertGreaterThan(0, $etlResult->getRaftCommandIndex());

            $this->assertGreaterThan(0, $etlResult->getTaskId());

            // and try to read ongoing sql task

            $ongoingTask = $src->maintenance()
                ->send(new GetOngoingTaskInfoOperation($etlResult->getTaskId(), OngoingTaskType::sqlEtl()));

            $this->assertNotNull($ongoingTask);

            $this->assertEquals($etlResult->getTaskId(), $ongoingTask->getTaskId());
            $this->assertEquals(OngoingTaskType::sqlEtl(), $ongoingTask->getTaskType());
            $this->assertNotNull($ongoingTask->getResponsibleNode());

            $this->assertEquals(OngoingTaskState::enabled(), $ongoingTask->getTaskState());
            $this->assertEquals("etlToDst", $ongoingTask->getTaskName());

            $configuration = $ongoingTask->getConfiguration();
            $transforms = $configuration->getTransforms();
            $this->assertCount(1, $transforms);

            $this->assertTrue($transforms[0]->isApplyToAllDocuments());

            $this->assertCount(1, $configuration->getSqlTables());
            $this->assertEquals("Users", $configuration->getSqlTables()[0]->getTableName());
        } finally {
            $src->close();
        }
    }

    public function testCanAddEtlWithScript(): void
    {
        $src = $this->getDocumentStore();
        try {
            $this->insertDocument($src);
            $result = $this->createConnectionString($src);

            $this->assertNotNull($result);

            $etlConfiguration = new SqlEtlConfiguration();
            $etlConfiguration->setConnectionStringName("toDst");
            $etlConfiguration->setDisabled(false);
            $etlConfiguration->setName("etlToDst");
            $transformation = new Transformation();
            $transformation->setApplyToAllDocuments(false);
            $transformation->setCollections(["Users"]);
            $transformation->setName("Script #1");
            $transformation->setScript("loadToUsers(this);");

            $table1 = new SqlEtlTable();
            $table1->setDocumentIdColumn("Id");
            $table1->setInsertOnlyMode(false);
            $table1->setTableName("Users");

            $etlConfiguration->setSqlTables([$table1]);
            $etlConfiguration->setTransforms([$transformation]);

            $operation = new AddEtlOperation($etlConfiguration);
            $etlResult = $src->maintenance()->send($operation);

            $this->assertNotNull($etlResult);

            $this->assertGreaterThan(0, $etlResult->getRaftCommandIndex());

            $this->assertGreaterThan(0, $etlResult->getTaskId());
        } finally {
            $src->close();
        }
    }

    public function testCanUpdateEtl(): void
    {
        $src = $this->getDocumentStore();
        try {
            $this->insertDocument($src);
            $result = $this->createConnectionString($src);

            $this->assertNotNull($result);

            $etlConfiguration = new SqlEtlConfiguration();
            $etlConfiguration->setConnectionStringName("toDst");
            $etlConfiguration->setDisabled(false);
            $etlConfiguration->setName("etlToDst");
            $transformation = new Transformation();
            $transformation->setApplyToAllDocuments(false);
            $transformation->setCollections(["Users"]);
            $transformation->setName("Script #1");
            $transformation->setScript("loadToUsers(this);");

            $table1 = new SqlEtlTable();
            $table1->setDocumentIdColumn("Id");
            $table1->setInsertOnlyMode(false);
            $table1->setTableName("Users");

            $etlConfiguration->setSqlTables([$table1]);
            $etlConfiguration->setTransforms([$transformation]);

            $operation = new AddEtlOperation($etlConfiguration);
            $etlResult = $src->maintenance()->send($operation);

            // now change ETL configuration

            $transformation->setCollections(["Cars"]);
            $transformation->setScript("loadToCars(this)");

            $updateResult = $src->maintenance()->send(new UpdateEtlOperation($etlResult->getTaskId(), $etlConfiguration));

            $this->assertNotNull($updateResult);

            $this->assertGreaterThan(0, $updateResult->getRaftCommandIndex());
            $this->assertGreaterThan(0, $updateResult->getTaskId());
        } finally {
            $src->close();
        }
    }

    public function testCanResetEtlTask(): void
    {
        $src = $this->getDocumentStore();
        try {
            $this->insertDocument($src);
            $result = $this->createConnectionString($src);

            $this->assertNotNull($result);

            $etlConfiguration = new SqlEtlConfiguration();
            $etlConfiguration->setConnectionStringName("toDst");
            $etlConfiguration->setDisabled(false);
            $etlConfiguration->setName("etlToDst");
            $transformation = new Transformation();
            $transformation->setApplyToAllDocuments(true);
            $transformation->setName("Script Q&A");

            $table1 = new SqlEtlTable();
            $table1->setDocumentIdColumn("Id");
            $table1->setInsertOnlyMode(false);
            $table1->setTableName("Users");

            $etlConfiguration->setSqlTables([$table1]);
            $etlConfiguration->setTransforms([$transformation]);

            $operation = new AddEtlOperation($etlConfiguration);
            $etlResult = $src->maintenance()->send($operation);

            $this->assertNotNull($etlResult);

            $this->assertGreaterThan(0, $etlResult->getRaftCommandIndex());

            $this->assertGreaterThan(0, $etlResult->getTaskId());

            $src->maintenance()->send(new ResetEtlOperation("etlToDst", "Script Q&A"));

            // we don't assert against real database
        } finally {
            $src->close();
        }
    }

    private function createConnectionString(DocumentStoreInterface $src): PutConnectionStringResult
    {
        $toDstLink = new SqlConnectionString();
        $toDstLink->setName("toDst");
        $toDstLink->setFactoryName("MySql.Data.MySqlClient");
        $toDstLink->setConnectionString("hostname=localhost;user=root;password=");

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
