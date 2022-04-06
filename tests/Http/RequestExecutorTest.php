<?php

namespace tests\RavenDB\Http;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStore;
use RavenDB\Http\GetDatabaseNamesResponse;
use RavenDB\Http\RequestExecutor;

use RavenDB\ServerWide\Operations\GetDatabaseNamesOperation;
use tests\RavenDB\RemoteTestBase;

class RequestExecutorTest extends RemoteTestBase
{
    public function testCanFetchDatabasesNames()
    {
        $conventions = new DocumentConventions();

        /** @var DocumentStore $store */
        $store = $this->getDocumentStore();

        try {
//            executor = RequestExecutor.create(
//                  store.getUrls(),
//                  store.getDatabase(),
//                  null,
//                  null,
//                  null,
//                  store.getExecutorService(),
//                  conventions
//            );
            $executor = RequestExecutor::create($store->getUrls(), $store->getDatabase(), $store->getAuthOptions(), $conventions);

            $databaseNamesOperation = new GetDatabaseNamesOperation(0, 20);

            $command = $databaseNamesOperation->getCommand($conventions);
            $executor->execute($command);

            /** @var GetDatabaseNamesResponse $dbNames */
            $dbNames = $command->getResult();

            $this->assertContains($store->getDatabase(), $dbNames->toArray());
        } finally {
            $store->close();
        }
    }
}
