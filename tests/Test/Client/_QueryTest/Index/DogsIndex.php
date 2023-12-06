<?php

namespace tests\RavenDB\Test\Client\_QueryTest\Index;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class DogsIndex extends AbstractIndexCreationTask
{
        public function __construct()
        {
            parent::__construct();
            $this->map = "from dog in docs.dogs select new { dog.name, dog.age, dog.vaccinated }";
        }
}
