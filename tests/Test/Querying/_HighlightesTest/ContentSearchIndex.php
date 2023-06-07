<?php

namespace tests\RavenDB\Test\Querying\_HighlightesTest;

use RavenDB\Documents\Indexes\AbstractMultiMapIndexCreationTask;
use RavenDB\Documents\Indexes\FieldIndexing;
use RavenDB\Documents\Indexes\FieldStorage;
use RavenDB\Documents\Indexes\FieldTermVector;

class ContentSearchIndex extends AbstractMultiMapIndexCreationTask
{
        public function __construct() {
            parent::__construct();

            $this->addMap("docs.EventsItems.Select(doc => new {\n" .
                    "    doc = doc,\n" .
                    "    slug = Id(doc).ToString().Substring(Id(doc).ToString().IndexOf('/') + 1)\n" .
                    "}).Select(this0 => new {\n" .
                    "    slug = this0.slug,\n" .
                    "    title = this0.doc.title,\n" .
                    "    content = this0.doc.content\n" .
                    "})");

            $this->index("slug", FieldIndexing::search());
            $this->store("slug", FieldStorage::yes());
            $this->termVector("slug", FieldTermVector::withPositionsAndOffsets());

            $this->index("title", FieldIndexing::search());
            $this->store("title", FieldStorage::yes());
            $this->termVector("title", FieldTermVector::withPositionsAndOffsets());

            $this->index("content", FieldIndexing::search());
            $this->store("content", FieldStorage::yes());
            $this->termVector("content", FieldTermVector::withPositionsAndOffsets());
        }
}
