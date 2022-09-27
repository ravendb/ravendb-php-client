<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

use RavenDB\Documents\Indexes\AbstractJavaScriptIndexCreationTask;
use RavenDB\Documents\Indexes\AdditionalSourcesArray;

class UsersByNameWithAdditionalSources extends AbstractJavaScriptIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();
        $this->setMaps(["map('Users', function(u) { return { name: mr(u.name)}; })"]);

        $additionalSources = new AdditionalSourcesArray();
        $additionalSources->offsetSet("The Script", "function mr(x) { return 'Mr. ' + x; }");
        $this->setAdditionalSources($additionalSources);
    }
}
