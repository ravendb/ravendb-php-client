<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15497Test;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class Index extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();
        $this->map = "from u in docs.Users select new { u.name }";
    }
}
