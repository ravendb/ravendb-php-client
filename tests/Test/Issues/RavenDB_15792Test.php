<?php

namespace tests\RavenDB\Test\Issues;

use DateTime;
use RavenDB\Documents\Queries\TimeSeries\TimeSeriesRawResult;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15792Test extends RemoteTestBase
{
     public function testCanQueryTimeSeriesWithSpacesInName(): void
     {
         $store = $this->getDocumentStore();
        try {
            $documentId = "users/ayende";

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);

                $tsf = $session->timeSeriesFor($documentId, "gas m3 usage");
                $tsf->append($baseLine, 1);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->advanced()->rawQuery(TimeSeriesRawResult::class,
                        "declare timeseries out()\n" .
                                "{\n" .
                                "    from \"gas m3 usage\"\n" .
                                "}\n" .
                                "from Users as u\n" .
                                "select out()");

                $result = $query->first();
                $this->assertNotNull($result);

                $results = $result->getResults();

                $this->assertCount(1, $results);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
