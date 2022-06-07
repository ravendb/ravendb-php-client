<?php

namespace tests\RavenDB\Test\Client\Issues\RavenDB_5669Test\Entity;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldIndexing;

class Animal_Index extends AbstractIndexCreationTask
{
        public function __construct()
        {
            parent::__construct();

            $this->map = "from animal in docs.Animals select new { name = animal.name, type = animal.type }";

            $this->analyze("name", "StandardAnalyzer");
            $this->index("name", FieldIndexing::search());
        }
}
