<?php

namespace tests\RavenDB\Documents\Commands;

use RavenDB\Documents\Commands\GetNextOperationIdCommand;
use tests\RavenDB\RemoteTestBase;

// !status: DONE
class GetNextOperationIdCommandTest extends RemoteTestBase
{
    public function testCanGetNextOperationId(): void
    {
        $store = $this->getDocumentStore();
        try {
            $command = new GetNextOperationIdCommand();

            $store->getRequestExecutor()->execute($command);

            $this->assertNotNull($command->getResult());
        } finally {
            $store->close();
        }
    }
}
