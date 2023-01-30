<?php

namespace tests\RavenDB\Test\Client\Indexing\TimeSeries;

use DateTime;
use RavenDB\Documents\Indexes\TimeSeries\TimeSeriesIndexDefinition;
use RavenDB\Documents\Operations\Indexes\GetTermsOperation;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RemoteTestBase;

class BasicTimeSeriesIndexes_MixedSyntaxTest extends RemoteTestBase
{
    public function testBasicMapIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $now1 = new DateTime();

            $session = $store->openSession();
            try {
                $company = new Company();
                $session->store($company, "companies/1");
                $session->timeSeriesFor($company, "HeartRate")
                        ->append($now1, 7, "tag");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $timeSeriesIndexDefinition = new TimeSeriesIndexDefinition();
            $timeSeriesIndexDefinition->setName("MyTsIndex");
            $timeSeriesIndexDefinition->setMaps(["from ts in timeSeries.Companies.HeartRate.Where(x => true) " .
                    "from entry in ts.Entries " .
                    "select new { " .
                    "   heartBeat = entry.Values[0], " .
                    "   date = entry.Timestamp.Date, " .
                    "   user = ts.DocumentId " .
                    "}"]);

            $store->maintenance()->send(new PutIndexesOperation($timeSeriesIndexDefinition));

            $this->waitForIndexing($store);

            $terms = $store->maintenance()->send(new GetTermsOperation("MyTsIndex", "heartBeat", null));
            $this->assertCount(1, $terms);
            $this->assertContains("7", $terms);
        } finally {
            $store->close();
        }
    }
}
