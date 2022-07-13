<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14006Test;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class Companies_ByName extends AbstractIndexCreationTask
{
    public function __construct() {
        parent::__construct();
        $this->map = "from c in docs.Companies select new { name = c.name }";
    }
}
