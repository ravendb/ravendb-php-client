<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15129Test;

use Exception;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RavenDB_15129Test extends RemoteTestBase
{
    public function testTimeSeriesValue_RequiresDoubleType(): void
    {
        $store = $this->getDocumentStore();
        try {
            try {
                $store->timeSeries()->register(Company::class, MetricValue::class);

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("Cannot create a mapping for", $exception->getMessage());
            }
        } finally {
            $store->close();
        }
    }
}
