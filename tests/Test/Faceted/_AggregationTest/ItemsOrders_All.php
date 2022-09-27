<?php

namespace tests\RavenDB\Test\Faceted\_AggregationTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class ItemsOrders_All extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "docs.ItemsOrders.Select(order => new { order.at,\n" .
                    "                          order.items })";
    }
}
