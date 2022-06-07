<?php

namespace tests\RavenDB\Documents\Operations\Indexes\IndexOperationsTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class UsersInvalidIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "from u in docs.Users select new { a = 5 / u.Age }";
    }
}
