<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15143Test;

use DateTime;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Documents\Session\TransactionMode;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15143Test extends RemoteTestBase
{
    public function testCanUseCreateCmpXngToInSessionWithNoOtherChanges(): void
    {
        $store = $this->getDocumentStore();
        try {
            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $session->store(new Command(), "cmd/239-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {

                $result = $session->query(Command::class)
                    ->include(function ($i) {
                        $i->includeCompareExchangeValue("id");
                    })
                    ->first();

                $this->assertNotNull($result);

                $locker = $session->advanced()
                        ->clusterTransaction()->getCompareExchangeValue(Locker::class, "cmd/239-A");
                $this->assertNull($locker);

                $lockerObject = new Locker();
                $lockerObject->setClientId("a");
                $locker = $session->advanced()->clusterTransaction()->createCompareExchangeValue("cmd/239-A", $lockerObject);

                $locker->getMetadata()->put("@expires", DateUtils::addMinutes(DateUtils::now(), 2));
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $smile = $session->advanced()->clusterTransaction()->getCompareExchangeValue(null, "cmd/239-A");
                $this->assertNotNull($smile);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanStoreAndReadPrimitiveCompareExchange(): void
    {
        $store = $this->getDocumentStore();
        try {
            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("cmd/int", 5);
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("cmd/string", "testing");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("cmd/true", true);
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("cmd/false", false);
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("cmd/zero", 0);
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("cmd/null", null);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $numberValue = $session
                        ->advanced()
                        ->clusterTransaction()
                        ->getCompareExchangeValue(null, "cmd/int");

                $this->assertIsNumeric($numberValue->getValue());
                $this->assertEquals(5, $numberValue->getValue());

                $stringValue = $session
                        ->advanced()
                        ->clusterTransaction()
                        ->getCompareExchangeValue(null, "cmd/string");
                $this->assertIsString("testing", $stringValue->getValue());
                $this->assertEquals("testing", $stringValue->getValue());

                $trueValue = $session
                        ->advanced()
                        ->clusterTransaction()
                        ->getCompareExchangeValue(null, "cmd/true");
                $this->assertIsBool($trueValue->getValue());
                $this->assertTrue($trueValue->getValue());
                $this->assertEquals(true, $trueValue->getValue());

                $falseValue = $session
                        ->advanced()
                        ->clusterTransaction()
                        ->getCompareExchangeValue(null, "cmd/false");
                $this->assertIsBool($falseValue->getValue());
                $this->assertFalse($falseValue->getValue());
                $this->assertEquals(false, $falseValue->getValue());

                $zeroValue = $session
                        ->advanced()
                        ->clusterTransaction()
                        ->getCompareExchangeValue(null, "cmd/zero");
                $this->assertEquals(0, $zeroValue->getValue());
                $this->assertIsNumeric($zeroValue->getValue());

                $nullValue = $session
                        ->advanced()
                        ->clusterTransaction()
                        ->getCompareExchangeValue(null, "cmd/null");
                $this->assertNull($nullValue->getValue());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
