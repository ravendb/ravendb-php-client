<?php

namespace tests\RavenDB\Test\Client\Indexing\Index;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class UsersIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();
        $this->map = "from user in docs.users select new { user.name }";
    }
}
