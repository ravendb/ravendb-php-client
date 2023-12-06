<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

use RavenDB\Documents\Indexes\AbstractJavaScriptIndexCreationTask;
use RavenDB\Type\StringSet;

class Products_ByCategory extends AbstractJavaScriptIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();
        $this->setMaps(StringSet::fromArray(["map('product2s', function(p){\n" .
                    "                        return {\n" .
                    "                            category:\n" .
                    "                            load(p.category, 'categories').name,\n" .
                    "                            count:\n" .
                    "                            1\n" .
                    "                        }\n" .
                    "                    })"]));

            $this->setReduce("groupBy( x => x.category )\n" .
                    "                            .aggregate(g => {\n" .
                    "                                return {\n" .
                    "                                    category: g.key,\n" .
                    "                                    count: g.values.reduce((count, val) => val.count + count, 0)\n" .
                    "                               };})");

            $this->setOutputReduceToCollection("CategoryCounts");
    }
}
