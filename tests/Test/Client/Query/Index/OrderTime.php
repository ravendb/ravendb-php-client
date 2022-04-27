<?php

namespace tests\RavenDB\Test\Client\Query\Index;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class OrderTime extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "from order in docs.Orders " .
                "select new { " .
                "  delay = order.shippedAt - ((DateTime?)order.orderedAt) " .
                "}";
    }
}
