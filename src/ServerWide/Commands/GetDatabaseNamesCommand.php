<?php

namespace RavenDB\ServerWide\Commands;

use RavenDB\Http\GetDatabaseNamesResponse;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetDatabaseNamesCommand extends RavenCommand
{

    private int $start;
    private int $pageSize;

    public function __construct(int $start = 0, int $pageSize = 20)
    {
        $this->start = $start;
        $this->pageSize = $pageSize;

        parent::__construct(GetDatabaseNamesResponse::class);
    }

    protected function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() .
            '/databases?start=' . $this->start .
            '&pageSize=' . $this->pageSize .
            '&namesOnly=true';
    }
}
