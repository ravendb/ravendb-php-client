<?php

namespace tests\RavenDB\Test\Issues;

use DateTime;
use RavenDB\Documents\Queries\TimeSeries\TimeSeriesRawResult;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15029Test extends RemoteTestBase
{
    public function testSessionRawQueryShouldNotTrackTimeSeriesResultAsDocument(): void
    {
        $store = $this->getDocumentStore();

        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Karmel");
                $session->store($user, "users/karmel");
                $session->timeSeriesFor("users/karmel", "HeartRate")
                        ->append($baseLine, 60, "watches/fitbit");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $u = $session->load(User::class, "users/karmel");
                $query = $session->advanced()->rawQuery(TimeSeriesRawResult::class,
                        "declare timeseries out()\n" .
                                "{\n" .
                                "    from HeartRate\n" .
                                "}\n" .
                                "from Users as u\n" .
                                "where name = 'Karmel'\n" .
                                "select out()");

                $result = $query->first();

                $this->assertEquals(1, $result->getCount());
                $this->assertEquals(60.0, $result->getResults()[0]->getValue());
                $this->assertEquals($baseLine, $result->getResults()[0]->getTimestamp());
                $this->assertEquals("watches/fitbit", $result->getResults()[0]->getTag());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
