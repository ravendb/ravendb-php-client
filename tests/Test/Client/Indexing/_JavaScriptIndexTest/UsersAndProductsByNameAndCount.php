<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

use RavenDB\Documents\Indexes\AbstractJavaScriptIndexCreationTask;

class UsersAndProductsByNameAndCount extends AbstractJavaScriptIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->setMaps(["map('Users', function (u){ return { name: u.name, count: 1};})", "map('Products', function (p){ return { name: p.name, count: 1};})"]);
        $this->setReduce("groupBy( x =>  x.name )\n" .
            "                                .aggregate(g => {return {\n" .
            "                                    name: g.key,\n" .
            "                                    count: g.values.reduce((total, val) => val.count + total,0)\n" .
            "                               };})");
    }
}
