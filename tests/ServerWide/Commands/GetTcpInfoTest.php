<?php

namespace tests\RavenDB\ServerWide\Commands;

use RavenDB\ServerWide\Commands\GetTcpInfoCommand;
use RavenDB\ServerWide\Commands\TcpConnectionInfo;
use tests\RavenDB\RemoteTestBase;

class GetTcpInfoTest extends RemoteTestBase
{
    public function testCanGetTcpInfo(): void
    {
        $store = $this->getDocumentStore();
        try {

            $command = new GetTcpInfoCommand("test");

            $store->getRequestExecutor()->execute($command);

            /** @var TcpConnectionInfo $result */
            $result = $command->getResult();

            $this->assertNotNull($result);

            $this->assertNull($result->getCertificate());

            $this->assertNotNull($result->getPort());

            $this->assertNotNull($result->getUrl());
        } finally {
            $store->close();
        }
    }
}
