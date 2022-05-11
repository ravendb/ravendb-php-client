<?php

namespace tests\RavenDB\Test\Client\Indexing\Index;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldIndexing;
use RavenDB\Documents\Indexes\FieldStorage;

class Posts_ByTitleAndDesc extends AbstractIndexCreationTask
{
    public function __construct() {
        parent::__construct();

        $this->map = "from p in docs.Posts select new { p.title, p.desc }";

        $this->index("title", FieldIndexing::search());
        $this->store("title", FieldStorage::yes());
        $this->analyze("title", "Lucene.Net.Analysis.SimpleAnalyzer");

        $this->index("desc", FieldIndexing::search());
        $this->store("desc", FieldStorage::yes());
        $this->analyze("desc", "Lucene.Net.Analysis.SimpleAnalyzer");
    }
}
