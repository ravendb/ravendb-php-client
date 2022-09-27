<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

use RavenDB\Documents\Indexes\AbstractJavaScriptIndexCreationTask;

class UsersByName extends AbstractJavaScriptIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();
        $this->setMaps(["map('Users', function (u) { return { name: u.name, count: 1 } })"]);
    }
}
