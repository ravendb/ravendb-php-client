<?php

namespace tests\RavenDB\Test\Client\Indexing\_IndexesFromClientTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldIndexing;
use RavenDB\Documents\Indexes\FieldStorage;

class Users_ByName extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "from u in docs.Users select new { u.name }";

        $this->index("name", FieldIndexing::search());

        $this->indexSuggestions->append("name");

        $this->store("name", FieldStorage::yes());
    }
}
