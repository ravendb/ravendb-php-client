<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

use RavenDB\Constants\DocumentsIndexingFields;
use RavenDB\Documents\Indexes\AbstractJavaScriptIndexCreationTask;
use RavenDB\Documents\Indexes\FieldIndexing;
use RavenDB\Documents\Indexes\IndexFieldOptions;
use RavenDB\Documents\Indexes\IndexFieldOptionsArray;

class UsersByNameAndAnalyzedName extends AbstractJavaScriptIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();
        $this->setMaps(["map('Users', function (u){\n" .
            "                                    return {\n" .
            "                                        name: u.name,\n" .
            "                                        _: {\$value: u.name, \$name:'analyzedName', \$options: { indexing: 'Search', storage: true}}\n" .
            "                                    };\n" .
            "                                })"]);

        $fieldOptions = new IndexFieldOptionsArray();

        $indexFieldOptions = new IndexFieldOptions();
        $indexFieldOptions->setIndexing(FieldIndexing::search());
        $indexFieldOptions->setAnalyzer("StandardAnalyzer");
        $fieldOptions->offsetSet(DocumentsIndexingFields::ALL_FIELDS, $indexFieldOptions);

        $this->setFields($fieldOptions);
    }
}
