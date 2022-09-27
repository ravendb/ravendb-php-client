<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

use RavenDB\Documents\Indexes\AbstractJavaScriptIndexCreationTask;

class FanoutByNumbersWithReduce extends AbstractJavaScriptIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();
        $this->setMaps(["map('Fanouts', function (f){\n" .
            "                                var result = [];\n" .
            "                                for(var i = 0; i < f.numbers.length; i++)\n" .
            "                                {\n" .
            "                                    result.push({\n" .
            "                                        foo: f.foo,\n" .
            "                                        sum: f.numbers[i]\n" .
            "                                    });\n" .
            "                                }\n" .
            "                                return result;\n" .
            "                                })"]);


            $this->setReduce("groupBy(f => f.foo).aggregate(g => ({  foo: g.key, sum: g.values.reduce((total, val) => val.sum + total,0) }))");
        }
}
