<?php

namespace RavenDB\ServerWide\Commands;

use RavenDB\Http\ClusterTopologyResponse;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetClusterTopologyCommand extends RavenCommand
{
    public function __construct()
    {
        parent::__construct(ClusterTopologyResponse::class);
    }

    protected function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . '/cluster/topology';
    }
}
