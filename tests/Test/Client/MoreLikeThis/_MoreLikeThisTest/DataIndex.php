<?php

namespace tests\RavenDB\Test\Client\MoreLikeThis\_MoreLikeThisTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldStorage;
use RavenDB\Documents\Indexes\FieldTermVector;

class DataIndex extends AbstractIndexCreationTask
{
        public function __construct($termVector = true, bool $store = false)
        {
            parent::__construct();

            $this->map = "from doc in docs.Data select new { doc.body, doc.whitespaceAnalyzerField }";

            $this->analyze("body", "Lucene.Net.Analysis.Standard.StandardAnalyzer");
            $this->analyze("whitespaceAnalyzerField", "Lucene.Net.Analysis.WhitespaceAnalyzer");

            if ($store) {
                $this->store("body", FieldStorage::yes());
                $this->store("whitespaceAnalyzerField", FieldStorage::yes());
            }

            if ($termVector) {
                $this->termVector("body", FieldTermVector::yes());
                $this->termVector("whitespaceAnalyzerField", FieldTermVector::yes());
            }
        }
}
