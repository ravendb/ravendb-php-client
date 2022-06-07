<?php

namespace tests\RavenDB\Test\Client\Issues\RavenDB903Test\Entity;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldIndexing;

class TestIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "from product in docs.Products select new { product.name, product.description }";

        $this->index("description", FieldIndexing::search());
    }
}
