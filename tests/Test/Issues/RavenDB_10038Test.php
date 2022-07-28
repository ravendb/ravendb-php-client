<?php

namespace tests\RavenDB\Test\Issues;

use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\Infrastructure\Entity\Person;
use RavenDB\Documents\Operations\DetailedDatabaseStatistics;
use RavenDB\Documents\Operations\GetDetailedStatisticsOperation;
use RavenDB\Documents\Operations\CompareExchange\PutCompareExchangeValueOperation;
use RavenDB\Documents\Operations\CompareExchange\DeleteCompareExchangeValueOperation;

class RavenDB_10038Test extends RemoteTestBase
{
    public function testCompareExchangeAndIdentitiesCount(): void
    {
        $store = $this->getDocumentStore();
        try {
            /** @var DetailedDatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetDetailedStatisticsOperation());
            $this->assertEquals(0, $stats->getCountOfIdentities());
            $this->assertEquals(0, $stats->getCountOfCompareExchange());

            $session = $store->openSession();
            try {
                $person = new Person();
                $person->setId("people|");
                $session->store($person);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DetailedDatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetDetailedStatisticsOperation());
            $this->assertEquals(1, $stats->getCountOfIdentities());
            $this->assertEquals(0, $stats->getCountOfCompareExchange());

            $session = $store->openSession();
            try {
                $person = new Person();
                $person->setId("people|");
                $session->store($person);

                $user = new User();
                $user->setId("users|");
                $session->store($user);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DetailedDatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetDetailedStatisticsOperation());
            $this->assertEquals(2, $stats->getCountOfIdentities());
            $this->assertEquals(0, $stats->getCountOfCompareExchange());

            $store->operations()->send(new PutCompareExchangeValueOperation("key/1", new Person(), 0));

            /** @var DetailedDatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetDetailedStatisticsOperation());
            $this->assertEquals(2, $stats->getCountOfIdentities());
            $this->assertEquals(1, $stats->getCountOfCompareExchange());

            $result = $store->operations()->send(new PutCompareExchangeValueOperation("key/2", new Person(), 0));
            $this->assertTrue($result->isSuccessful());

            /** @var DetailedDatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetDetailedStatisticsOperation());
            $this->assertEquals(2, $stats->getCountOfIdentities());
            $this->assertEquals(2, $stats->getCountOfCompareExchange());


            $result = $store->operations()->send(new PutCompareExchangeValueOperation("key/2", new Person(), $result->getIndex()));
            $this->assertTrue($result->isSuccessful());

            /** @var DetailedDatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetDetailedStatisticsOperation());
            $this->assertEquals(2, $stats->getCountOfIdentities());
            $this->assertEquals(2, $stats->getCountOfCompareExchange());

            $result = $store->operations()->send(new DeleteCompareExchangeValueOperation(Person::class, "key/2", $result->getIndex()));
            $this->assertTrue($result->isSuccessful());

            /** @var DetailedDatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetDetailedStatisticsOperation());
            $this->assertEquals(2, $stats->getCountOfIdentities());
            $this->assertEquals(1, $stats->getCountOfCompareExchange());

        } finally {
            $store->close();
        }
    }
}
