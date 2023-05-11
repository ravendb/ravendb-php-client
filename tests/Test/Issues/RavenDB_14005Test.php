<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Documents\Session\TransactionMode;
use tests\RavenDB\Infrastructure\Orders\Address;
use tests\RavenDB\RemoteTestBase;

class RavenDB_14005Test extends RemoteTestBase
{
    public function testCanGetCompareExchangeValuesLazilyNoTracking(): void {
        $this->canGetCompareExchangeValuesLazily(true);
    }

    public function testCanGetCompareExchangeValuesLazilyWithTracking(): void {
        $this->canGetCompareExchangeValuesLazily(false);
    }

    public function canGetCompareExchangeValuesLazily(bool $noTracking): void {
        $store = $this->getDocumentStore();
        try {
            $sessionOptions = new SessionOptions();
            $sessionOptions->setNoTracking($noTracking);
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $lazyValue = $session->advanced()->clusterTransaction()->lazily()->getCompareExchangeValue(Address::class, "companies/hr");

                $this->assertFalse($lazyValue->isValueCreated());

                $address = $lazyValue->getValue();
                $this->assertNull($address);

                $value = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, "companies/hr");
                $this->assertEquals($address, $value);

                $lazyValues = $session->advanced()
                        ->clusterTransaction()->lazily()
                        ->getCompareExchangeValues(Address::class, [ "companies/hr", "companies/cf" ]);

                $this->assertFalse($lazyValues->isValueCreated());

                $addresses = $lazyValues->getValue();

                $this->assertNotNull($addresses);
                $this->assertCount(2, $addresses);
                $this->assertArrayHasKey("companies/hr", $addresses);
                $this->assertArrayHasKey("companies/cf", $addresses);

                $this->assertNull($addresses["companies/hr"]);
                $this->assertNull($addresses["companies/cf"]);

                $values = $session->advanced()->clusterTransaction()
                        ->getCompareExchangeValues(Address::class, [ "companies/hr", "companies/cf" ]);

                $this->assertArrayHasKey("companies/hr", $values);
                $this->assertArrayHasKey("companies/cf", $values);

                $this->assertEquals($values["companies/hr"], $addresses["companies/hr"]);
                $this->assertEquals($values["companies/cf"], $addresses["companies/cf"]);
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $lazyValue = $session->advanced()
                        ->clusterTransaction()
                        ->lazily()
                        ->getCompareExchangeValue(Address::class, "companies/hr");
                $lazyValues = $session->advanced()
                        ->clusterTransaction()
                        ->lazily()
                        ->getCompareExchangeValues(Address::class, [ "companies/hr", "companies/cf" ]);

                $this->assertFalse($lazyValue->isValueCreated());
                $this->assertFalse($lazyValues->isValueCreated());

                $session->advanced()->eagerly()->executeAllPendingLazyOperations();

                $numberOfRequests = $session->advanced()->getNumberOfRequests();

                $address = $lazyValue->getValue();

                $this->assertEquals($numberOfRequests, $session->advanced()->getNumberOfRequests());

                $this->assertNull($address);

                $addresses = $lazyValues->getValue();

                $this->assertEquals($numberOfRequests, $session->advanced()->getNumberOfRequests());

                $this->assertNotNull($addresses);
                $this->assertCount(2, $addresses);
                $this->assertArrayHasKey("companies/hr", $addresses);
                $this->assertArrayHasKey("companies/cf", $addresses);

                $this->assertNull($addresses["companies/hr"]);
                $this->assertNull($addresses["companies/cf"]);
            } finally {
                $session->close();
            }

            $clusterSessionOptions = new SessionOptions();
            $clusterSessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($clusterSessionOptions);
            try {
                $address1 = new Address();
                $address1->setCity("Hadera");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/hr", $address1);

                $address2 = new Address();
                $address2->setCity("Torun");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/cf", $address2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $lazyValue = $session
                        ->advanced()
                        ->clusterTransaction()
                        ->lazily()
                        ->getCompareExchangeValue(Address::class, "companies/hr");

                $this->assertFalse($lazyValue->isValueCreated());

                $address = $lazyValue->getValue();
                $this->assertNotNull($address);

                $this->assertEquals("Hadera", $address->getValue()->getCity());

                $value = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, "companies/hr");
                $this->assertEquals($address->getValue()->getCity(), $value->getValue()->getCity());

                $lazyValues = $session
                        ->advanced()
                        ->clusterTransaction()
                        ->lazily()
                        ->getCompareExchangeValues(Address::class, [ "companies/hr", "companies/cf" ]);

                $this->assertFalse($lazyValues->isValueCreated());

                $addresses = $lazyValues->getValue();

                $this->assertNotNull($addresses);
                $this->assertCount(2, $addresses);
                $this->assertArrayHasKey("companies/hr", $addresses);
                $this->assertArrayHasKey("companies/hr", $addresses);
                $this->assertArrayHasKey("companies/cf", $addresses);

                $this->assertEquals("Hadera", $addresses["companies/hr"]->getValue()->getCity());
                $this->assertEquals("Torun", $addresses["companies/cf"]->getValue()->getCity());

                $values = $session
                        ->advanced()
                        ->clusterTransaction()
                        ->getCompareExchangeValues(Address::class, [ "companies/hr", "companies/cf" ]);

                $this->assertArrayHasKey("companies/hr", $values);
                $this->assertArrayHasKey("companies/cf", $values);

                $this->assertEquals($values["companies/hr"]->getValue()->getCity(), $addresses["companies/hr"]->getValue()->getCity());
                $this->assertEquals($values["companies/cf"]->getValue()->getCity(), $addresses["companies/cf"]->getValue()->getCity());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
