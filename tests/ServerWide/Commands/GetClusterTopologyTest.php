<?php

declare(strict_types=1);

namespace tests\RavenDB\ServerWide\Commands;

use RavenDB\Http\ClusterTopology;
use RavenDB\Http\ClusterTopologyResponse;
use RavenDB\ServerWide\Commands\GetClusterTopologyCommand;

use tests\RavenDB\RemoteTestBase;

class GetClusterTopologyTest extends RemoteTestBase
{
    public function testCanGetTopology(): void
    {
        $store = $this->getDocumentStore();

        $command = new GetClusterTopologyCommand();

        $store->getRequestExecutor()
            ->execute($command);

        /** @var ClusterTopologyResponse $result */
        $result = $command->getResult();

        $this->assertNotNull($result);

        $this->assertNotNull($result->getLeader());

        $this->assertNotNull($result->getNodeTag());

        /** @var ClusterTopology $topology */
        $topology = $result->getTopology();

        print_r($topology);

        $this->assertNotNull($topology);

        $this->assertNotNull($topology->getTopologyId());

        $this->assertIsArray($topology->getMembers());
        $this->assertEquals(1, count($topology->getMembers()));

        $this->assertIsArray($topology->getWatchers());
        $this->assertEquals(0, count($topology->getWatchers()));

        $this->assertIsArray($topology->getPromotables());
        $this->assertEquals(0, count($topology->getPromotables()));
    }
}
