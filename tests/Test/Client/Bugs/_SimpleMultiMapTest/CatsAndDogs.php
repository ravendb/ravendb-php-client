<?php

namespace tests\RavenDB\Test\Client\Bugs\_SimpleMultiMapTest;

use RavenDB\Documents\Indexes\AbstractMultiMapIndexCreationTask;

class CatsAndDogs extends AbstractMultiMapIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();
        $this->addMap("from cat in docs.Cats select new { cat.name }");
        $this->addMap("from dog in docs.Dogs select new { dog.name }");
    }
}
