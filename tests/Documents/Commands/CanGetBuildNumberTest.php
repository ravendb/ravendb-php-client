<?php

namespace tests\RavenDB\Documents\Commands;

use RavenDB\ServerWide\Operations\BuildNumber;
use RavenDB\ServerWide\Operations\GetBuildNumberOperation;
use tests\RavenDB\RemoteTestBase;

class CanGetBuildNumberTest extends RemoteTestBase
{
    public function testCanGetBuildNumber(): void
    {
        $store = $this->getDocumentStore();
        try {
            /** @var BuildNumber $buildNumber */
            $buildNumber = $store->maintenance()->server()->send(new GetBuildNumberOperation());

            $this->assertNotNull($buildNumber);
            $this->assertNotNull($buildNumber->getProductVersion());
        } finally {
            $store->close();
        }
    }
}
