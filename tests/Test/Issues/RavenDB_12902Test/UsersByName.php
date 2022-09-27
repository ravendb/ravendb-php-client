<?php

namespace tests\RavenDB\Test\Issues\RavenDB_12902Test;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class UsersByName extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();
        $this->map = "from u in docs.Users select new " .
                    " {" .
                    "    firstNAme = u.name, " .
                    "    lastName = u.lastName" .
                    "}";

        $this->suggestion("name");
    }
}
