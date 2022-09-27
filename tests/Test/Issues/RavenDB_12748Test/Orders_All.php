<?php

namespace tests\RavenDB\Test\Issues\RavenDB_12748Test;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class Orders_All extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "docs.Orders.Select(order => new { order.currency,\n" .
            "                          order.product,\n" .
            "                          order.total,\n" .
            "                          order.quantity,\n" .
            "                          order.region,\n" .
            "                          order.at,\n" .
            "                          order.tax })";
    }
}
