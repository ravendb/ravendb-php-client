<?php

namespace tests\RavenDB;

use RavenDB\Type\StringArray;
use tests\RavenDB\Driver\RavenServerLocator;

class TestServiceLocator extends RavenServerLocator
{
    public function getCommandArguments(): StringArray
    {
        $stringArray = new StringArray();

        $stringArray->append("--ServerUrl=http://127.0.0.1:8080");
        $stringArray->append("--ServerUrl.Tcp=tcp://127.0.0.1:38881");
        $stringArray->append("--Features.Availability=Experimental");

        return $stringArray;
    }
}
