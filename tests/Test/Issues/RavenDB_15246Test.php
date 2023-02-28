<?php

namespace tests\RavenDB\Test\Issues;

use DateTime;
use RavenDB\Constants\PhpClient;
use RavenDB\Documents\Operations\TimeSeries\GetMultipleTimeSeriesCommand;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRange;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15246Test extends RemoteTestBase
{
    public function testClientCacheWithPageSize(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $session->store(new User(), "users/1-A");
                $tsf = $session->timeSeriesFor("users/1-A", "Heartrate");
                for ($i = 0; $i <= 20; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), [$i], "watches/apple");
                }
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $ts = $session->timeSeriesFor($user, "Heartrate");
                $res = $ts->get(null, null, null, 0, 0);
                $this->assertEmpty($res);

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $res = $ts->get(null, null, null, 0, 10);
                $this->assertCount(10, $res);

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $res = $ts->get(null, null, null, 0, 7);
                $this->assertCount(7, $res);

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $res = $ts->get(null, null, null, 0, 20);

                $this->assertCount(20, $res);
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $res = $ts->get(null, null, null, 0, 25);

                $this->assertCount(21, $res);
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testRanges(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());
            $id = "users/1-A";

            $session = $store->openSession();
            try {
                $session->store(new User(), $id);
                $tsf = $session->timeSeriesFor($id, "raven");
                for ($i = 0; $i <= 10; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), [$i], "watches/apple");
                }
                for ($i = 12; $i <= 13; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), [$i], "watches/apple");
                }
                for ($i = 16; $i <= 20; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), [$i], "watches/apple");
                }
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $rangesList = [];
            $timeSeriesRange = new TimeSeriesRange();
            $timeSeriesRange->setName("raven");
            $timeSeriesRange->setFrom(DateUtils::addMinutes($baseLine, 1));
            $timeSeriesRange->setTo(DateUtils::addMilliseconds($baseLine, 7));
            $rangesList[] = $timeSeriesRange;

            $re = $store->getRequestExecutor();
            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, PhpClient::INT_MAX_VALUE);
            $re->execute($tsCommand);
            $res = $tsCommand->getResult();

            $this->assertCount(1, $res->getValues());
            $this->assertCount(1, $res->getValues()["raven"]);

            $rangesList = [
                new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 8), DateUtils::addMinutes($baseLine, 11))
            ];

            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, PhpClient::INT_MAX_VALUE);
            $re->execute($tsCommand);
            $res = $tsCommand->getResult();

            $this->assertCount(1, $res->getValues());
            $this->assertCount(1, $res->getValues()["raven"]);

            $rangesList = [
                new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 8), DateUtils::addMinutes($baseLine, 17))
            ];

            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, PhpClient::INT_MAX_VALUE);
            $re->execute($tsCommand);
            $res = $tsCommand->getResult();

            $this->assertCount(1, $res->getValues());
            $this->assertCount(1, $res->getValues()["raven"]);
//
            $rangesList = [
                    new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 14), DateUtils::addMinutes($baseLine, 15))
            ];

            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, PhpClient::INT_MAX_VALUE);
            $re->execute($tsCommand);
            $res = $tsCommand->getResult();

            $this->assertCount(1, $res->getValues());
            $this->assertCount(1, $res->getValues()["raven"]);

            $rangesList = [
                    new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 23), DateUtils::addMinutes($baseLine, 25))
            ];

            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, PhpClient::INT_MAX_VALUE);
            $re->execute($tsCommand);
            $res = $tsCommand->getResult();

            $this->assertCount(1, $res->getValues());
            $this->assertCount(1, $res->getValues()["raven"]);


            $rangesList = [
                    new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 20), DateUtils::addMinutes($baseLine, 26))
            ];

            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, PhpClient::INT_MAX_VALUE);
            $re->execute($tsCommand);
            $res = $tsCommand->getResult();

            $this->assertCount(1, $res->getValues());
            $this->assertCount(1, $res->getValues()["raven"]);

        } finally {
            $store->close();
        }
    }


    public function testClientCacheWithStart(): void {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $session->store(new User(), "users/1-A");
                $tsf = $session->timeSeriesFor("users/1-A", "Heartrate");
                for ($i = 0; $i < 20; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), [ $i ], "watches/apple");
                }
                $session->saveChanges();
            } finally {
               $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1-A");
                $ts = $session->timeSeriesFor($user, "Heartrate");

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $res = $ts->get(null, null, null, 20, PhpClient::INT_MAX_VALUE);

                $this->assertCount(0, $res);
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());


                $res = $ts->get(null, null, null, 10, PhpClient::INT_MAX_VALUE);
                $this->assertCount(10, $res);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $res[0]->getTimestamp());

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());

                $res = $ts->get(null, null, null, 0, PhpClient::INT_MAX_VALUE);
                $this->assertCount(20, $res);

                $this->assertEquals($baseLine, $res[0]->getTimestamp());
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $res[10]->getTimestamp());
                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                $res = $ts->get(null, null, null, 10, PhpClient::INT_MAX_VALUE);
                $this->assertCount(10, $res);
                $this->assertEquals(DateUtils::addMinutes($baseLine, 10), $res[0]->getTimestamp());
                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());

                $res = $ts->get(null, null, null, 20, PhpClient::INT_MAX_VALUE);
                $this->assertEmpty($res);
            } finally {
               $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testGetResultsWithRange(): void {
        $store = $this->getDocumentStore();
        try {
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());
            $id = "users/1-A";
            $session = $store->openSession();
            try {
                $session->store(new User(),$id);
                $tsf = $session->timeSeriesFor($id, "raven");
                for ($i = 0; $i < 8; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), [ 64 ], "watches/apple");
                }

                $session->saveChanges();

                $rangesList = [
                        new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 0), DateUtils::addMinutes($baseLine, 3)),
                        new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 4), DateUtils::addMinutes($baseLine, 7)),
                        new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 8), DateUtils::addMinutes($baseLine, 11))
                ];

                $re = $store->getRequestExecutor();

                $tsCommand
                        = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, 10);
                $re->execute($tsCommand);

                $res = $tsCommand->getResult();

                $this->assertCount(1, $res->getValues());
                $this->assertCount(3, $res->getValues()["raven"]);

                $this->assertCount(4, $res->getValues()["raven"][0]->getEntries());
                $this->assertCount(4, $res->getValues()["raven"][1]->getEntries());
                $this->assertCount(0, $res->getValues()["raven"][2]->getEntries());

                $tsf = $session->timeSeriesFor($id, "raven");
                for ($i = 8; $i < 11; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), [ 1000 ], "watches/apple");
                }

                $session->saveChanges();

                $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, 10);

                $re->execute($tsCommand);

                $res = $tsCommand->getResult();

                $this->assertCount(1, $res->getValues());

                $this->assertCount(3, $res->getValues()["raven"]);

                $this->assertCount(4, $res->getValues()["raven"][0]->getEntries());
                $this->assertCount(4, $res->getValues()["raven"][1]->getEntries());
                $this->assertCount(2, $res->getValues()["raven"][2]->getEntries());
            } finally {
               $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testResultsWithRangeAndPageSize(): void {
        $store = $this->getDocumentStore();
        try {
            $tag = "raven";
            $id = "users/1";
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $session->store(new User(), $id);
                $tsf = $session->timeSeriesFor($id, $tag);
                for ($i = 0; $i <= 15; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), [ $i ], "watches/apple");
                }
                $session->saveChanges();
            } finally {
               $session->close();
            }

            $rangesList = [
                    new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 0), DateUtils::addMinutes($baseLine, 3)),
                    new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 4), DateUtils::addMinutes($baseLine, 7)),
                    new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 8), DateUtils::addMinutes($baseLine, 11))
            ];

            $re = $store->getRequestExecutor();

            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, 0);
            $re->execute($tsCommand);

            $res = $tsCommand->getResult();
            $this->assertEmpty($res->getValues());

            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, 30);
            $re->execute($tsCommand);

            $res = $tsCommand->getResult();

            $this->assertCount(1, $res->getValues());
            $this->assertCount(3, $res->getValues()["raven"]);

            $this->assertCount(4, $res->getValues()["raven"][0]->getEntries());
            $this->assertCount(4, $res->getValues()["raven"][1]->getEntries());
            $this->assertCount(4, $res->getValues()["raven"][2]->getEntries());

            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, 6);
            $re->execute($tsCommand);

            $res = $tsCommand->getResult();

            $this->assertCount(1, $res->getValues());
            $this->assertCount(2, $res->getValues()["raven"]);

            $this->assertCount(4, $res->getValues()["raven"][0]->getEntries());
            $this->assertCount(2, $res->getValues()["raven"][1]->getEntries());
        } finally {
            $store->close();
        }
    }

    public function testResultsWithRangeAndStart(): void {
        $store = $this->getDocumentStore();
        try {
            $tag = "raven";
            $id = "users/1";
            $baseLine = DateUtils::truncateDayOfMonth(new DateTime());

            $session = $store->openSession();
            try {
                $session->store(new User(),$id);
                $tsf = $session->timeSeriesFor($id, $tag);
                for ($i = 0; $i <= 15; $i++) {
                    $tsf->append(DateUtils::addMinutes($baseLine, $i), [ $i ], "watches/apple");
                }
                $session->saveChanges();
            } finally {
               $session->close();
            }

            $rangesList = [
                    new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 0), DateUtils::addMinutes($baseLine, 3)),
                    new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 4), DateUtils::addMinutes($baseLine, 7)),
                    new TimeSeriesRange("raven", DateUtils::addMinutes($baseLine, 8), DateUtils::addMinutes($baseLine, 11))
            ];

            $re = $store->getRequestExecutor();

            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 0, 20);

            $re->execute($tsCommand);

            $res = $tsCommand->getResult();

            $this->assertCount(1, $res->getValues());
            $this->assertCount(3, $res->getValues()["raven"]);

            $this->assertCount(4, $res->getValues()["raven"][0]->getEntries());
            $this->assertCount(4, $res->getValues()["raven"][1]->getEntries());
            $this->assertCount(4, $res->getValues()["raven"][2]->getEntries());

            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 3, 20);
            $re->execute($tsCommand);

            $res = $tsCommand->getResult();

            $this->assertCount(1, $res->getValues());
            $this->assertCount(3, $res->getValues()["raven"]);

            $this->assertCount(1, $res->getValues()["raven"][0]->getEntries());
            $this->assertCount(4, $res->getValues()["raven"][1]->getEntries());
            $this->assertCount(4, $res->getValues()["raven"][2]->getEntries());

            $tsCommand = new GetMultipleTimeSeriesCommand($id, $rangesList, 9, 20);
            $re->execute($tsCommand);

            $res = $tsCommand->getResult();

            $this->assertCount(1, $res->getValues());
            $this->assertCount(3, $res->getValues()["raven"]);

            $this->assertCount(0, $res->getValues()["raven"][0]->getEntries());
            $this->assertCount(0, $res->getValues()["raven"][1]->getEntries());
            $this->assertCount(3, $res->getValues()["raven"][2]->getEntries());
        } finally {
            $store->close();
        }
    }
}
