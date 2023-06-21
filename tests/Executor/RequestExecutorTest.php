<?php

namespace tests\RavenDB\Executor;

use Composer\Platform\Runtime;
use Exception;
use RavenDB\Documents\Commands\GetNextOperationIdCommand;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\Database\DatabaseDoesNotExistException;
use RavenDB\Http\RequestExecutor;
use RavenDB\Http\ServerNode;
use RavenDB\Http\UpdateTopologyParameters;
use RavenDB\ServerWide\Operations\GetDatabaseNamesOperation;
use RuntimeException;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RequestExecutorTest extends RemoteTestBase
{
//    public function failuresDoesNotBlockConnectionPool(): void {
//        DocumentConventions conventions = new DocumentConventions();
//
//        try (DocumentStore store = getDocumentStore()) {
//            try (RequestExecutor executor = RequestExecutor.create(store.getUrls(), "no_such_db", null, null,null, store.getExecutorService(), conventions)) {
//                int errorsCount = 0;
//
//                for (int i = 0; i < 40; i++) {
//                    try {
//                        GetNextOperationIdCommand command = new GetNextOperationIdCommand();
//                        executor.execute(command);
//                    } catch (Exception e) {
//                        errorsCount++;
//                    }
//                }
//
//                assertThat(errorsCount).isEqualTo(40);
//
//                assertThatThrownBy(() -> {
//                    GetDatabaseNamesOperation databaseNamesOperation = new GetDatabaseNamesOperation(0, 20);
//                    RavenCommand<String[]> command = databaseNamesOperation.getCommand(conventions);
//                    executor.execute(command);
//                }).isExactlyInstanceOf(DatabaseDoesNotExistException.class);
//            }
//        }
//    }

    /** @doesNotPerformAssertions */
    public function testCanIssueManyRequests(): void
    {
        $conventions = new DocumentConventions();

        $store = $this->getDocumentStore();
        try {
            $executor = RequestExecutor::create($store->getUrls(), $store->getDatabase(), null, $conventions);
            try {
                for ($i = 0; $i < 50; $i++) {
                    $databaseNamesOperation = new GetDatabaseNamesOperation(0, 20);
                    $command = $databaseNamesOperation->getCommand($conventions);
                    $executor->execute($command);
                }
            } finally {
                $executor->close();
            }
        } finally {
            $store->close();
        }
    }

//    public function canFetchDatabasesNames(): void {
//        DocumentConventions conventions = new DocumentConventions();
//
//        try (DocumentStore store = getDocumentStore()) {
//            try (RequestExecutor executor = RequestExecutor.create(store.getUrls(), store.getDatabase(), null, null, null, store.getExecutorService(), conventions)) {
//                GetDatabaseNamesOperation databaseNamesOperation = new GetDatabaseNamesOperation(0, 20);
//                RavenCommand<String[]> command = databaseNamesOperation.getCommand(conventions);
//                executor.execute(command);
//
//                String[] dbNames = command.getResult();
//
//                assertThat(dbNames).contains(store.getDatabase());
//            }
//        }
//    }

    public function testThrowsWhenUpdatingTopologyOfNotExistingDb(): void
    {
        $conventions = new DocumentConventions();

        $store = $this->getDocumentStore();
        try {
            $executor = RequestExecutor::create($store->getUrls(), "no_such_db", null, $conventions);
            try {
                $serverNode = new ServerNode();
                $serverNode->setUrl($store->getUrls()[0]);
                $serverNode->setDatabase("no_such");

                $updateTopologyParameters = new UpdateTopologyParameters($serverNode);
                $updateTopologyParameters->setTimeoutInMs(5000);

                try {
                    $executor->updateTopologyAsync($updateTopologyParameters);

                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(DatabaseDoesNotExistException::class, $exception);
                }
            } finally {
                $executor->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testThrowsWhenDatabaseDoesNotExist(): void
    {
        $conventions = new DocumentConventions();

        $store = $this->getDocumentStore();
        try {
            $executor = RequestExecutor::create($store->getUrls(), "no_such_db", null, $conventions);
            try {
                $command = new GetNextOperationIdCommand();

                $this->expectException(DatabaseDoesNotExistException::class);
                $executor->execute($command);
            } finally {
                $executor->close();
            }
        } finally {
            $store->close();
        }
    }


    public function testCanCreateSingleNodeRequestExecutor(): void
    {
        $documentConventions = new DocumentConventions();
        $store = $this->getDocumentStore();
        try {
            $executor = RequestExecutor::createForSingleNodeWithoutConfigurationUpdates($store->getUrls()[0], $store->getDatabase(), null, $documentConventions);
            try {
                $nodes = $executor->getTopologyNodes();

                $this->assertCount(1, $nodes);

                $serverNode = $nodes[0];
                $this->assertEquals($store->getUrls()[0], $serverNode->getUrl());
                $this->assertEquals($store->getDatabase(), $serverNode->getDatabase());

                $command = new GetNextOperationIdCommand();

                $executor->execute($command);

                $this->assertNotNull($command->getResult());
            } finally {
                $executor->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanChooseOnlineNode(): void
    {
        $documentConventions = new DocumentConventions();

        $store = $this->getDocumentStore();
        try {
            $url = $store->getUrls()[0];
            $dbName = $store->getDatabase();

            $executor = RequestExecutor::create(
                ["http://no-such-host:8080", "http://another-offline:8080", $url],
                $dbName,
                null,
                $documentConventions
            );
            try {
                $command = new GetNextOperationIdCommand();
                $executor->execute($command);

                $this->assertNotNull($command->getResult());

                $topologyNodes = $executor->getTopologyNodes();

                $this->assertCount(1, $topologyNodes);

                $this->assertEquals($url->getValue(), $topologyNodes[0]->getUrl()->getValue());

                $this->assertEquals($url->getValue(), $executor->getUrl());
            } finally {
                $executor->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testFailsWhenServerIsOffline(): void
    {
        $documentConventions = new DocumentConventions();

        $store = $this->getDocumentStore();
        try {
            $executor = RequestExecutor::create(["http://no-such-host:8081"], "db1", null, $documentConventions);
            try {
                try {
                    $command = new GetNextOperationIdCommand();

                    // don't even start server
                    $executor->execute($command);
                } finally {
                    $executor->close();
                }
            } catch (\Throwable $exception) {
                $cause = $exception->getPrevious();

                $this->assertNotNull($cause);
                $this->assertInstanceOf(Exception::class, $cause);
                $this->assertStringStartsWith('Could not resolve host: no-such-host for "http://no-such-host:8081/databases/db1/operations/next-operation-id"', $cause->getMessage());
            }
        } finally {
            $store->close();
        }
    }
}
