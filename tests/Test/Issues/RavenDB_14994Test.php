<?php

namespace tests\RavenDB\Test\Issues;

use DateTime;
use RavenDB\Documents\Operations\TimeSeries\GetTimeSeriesOperation;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_14994Test extends RemoteTestBase
{
    public function testGetOnNonExistingTimeSeriesShouldReturnNull(): void
    {
        $store = $this->getDocumentStore();
        try {
            $documentId = "users/ayende";

            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $get = $store->operations()->send(new GetTimeSeriesOperation($documentId, "HeartRate"));
            $this->assertNull($get);

            $session = $store->openSession();
            try {
                $this->assertNull($session->timeSeriesFor($documentId, "HeartRate")->get());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testGetOnEmptyRangeShouldReturnEmptyArray(): void
    {
        $store = $this->getDocumentStore();
        try {
            $documentId = "users/ayende";

            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $session->store(new User(), $documentId);

                $tsf = $session->timeSeriesFor($documentId, "HeartRate");
                for ($i = 0; $i < 10; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), $i);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $get = $store->operations()->send(new GetTimeSeriesOperation($documentId, "HeartRate", DateUtils::addMinutes($baseLine, -2), DateUtils::addMinutes($baseLine, -1)));
            $this->assertEmpty($get->getEntries());

            $session = $store->openSession();
            try {
                $result = $session->timeSeriesFor($documentId, "HeartRate")
                        ->get(DateUtils::addMonths($baseLine, -2), DateUtils::addMonths($baseLine, -1));
                $this->assertEmpty($result);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
