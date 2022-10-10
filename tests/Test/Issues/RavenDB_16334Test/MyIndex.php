<?php

namespace tests\RavenDB\Test\Issues\RavenDB_16334Test;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldStorage;

class MyIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "docs.MainDocuments.Select(mainDocument => new {" .
            "    mainDocument = mainDocument," .
            "    related = this.LoadDocument(String.Format(\"related/{0}\", mainDocument.name), \"RelatedDocuments\")" .
            "}).Select(this0 => new {" .
            "    name = this0.mainDocument.name,\n" .
            "    value = this0.related != null ? ((decimal ? ) this0.related.value) : ((decimal ? ) null)\n" .
            "})";

        $this->storeAllFields(FieldStorage::yes());
    }
}
