<?php

namespace tests\RavenDB\ServerWide\Commands;

use RavenDB\Http\ServerNode;
use RavenDB\Http\Topology;
use RavenDB\ServerWide\Commands\GetDatabaseTopologyCommand;
use tests\RavenDB\RemoteTestBase;

class GetTopologyTest extends RemoteTestBase
{
    public function testCanGetTopology(): void
    {
        $store = $this->getDocumentStore();
        try {

            $command = new GetDatabaseTopologyCommand();

            $store->getRequestExecutor()->execute($command);

            /** @var Topology $result */
            $result = $command->getResult();

            $this->assertNotNull($result);

            $this->assertNotNull($result->getEtag());

            $this->assertCount(1, $result->getNodes());

            /** @var ServerNode $serverNode */
            $serverNode = $result->getNodes()->first();

            $this->assertEquals($store->getUrls()->first(), $serverNode->getUrl());

            $this->assertEquals($store->getDatabase(), $serverNode->getDatabase());

            $this->assertEquals('A', $serverNode->getClusterTag());

            $this->assertTrue($serverNode->getServerRole()->isMember());

        } finally {
            $store->close();
        }
    }
}
