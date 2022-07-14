<?php

namespace tests\RavenDB\Test\Client;

use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Infrastructure\Entity\User;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Documents\Session\TransactionMode;
use RavenDB\Documents\Operations\DetailedDatabaseStatistics;
use RavenDB\Documents\Operations\GetDetailedStatisticsOperation;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeResult;
use RavenDB\Documents\Operations\CompareExchange\GetCompareExchangeValueOperation;
use RavenDB\Documents\Operations\CompareExchange\PutCompareExchangeValueOperation;
use RavenDB\Documents\Operations\CompareExchange\GetCompareExchangeValuesOperation;
use RavenDB\Documents\Operations\CompareExchange\DeleteCompareExchangeValueOperation;

class UniqueValuesTest extends RemoteTestBase
{
    public function testCanReadNotExistingKey(): void
    {
        $store = $this->getDocumentStore();
        try {
            $res = $store->operations()->send(new GetCompareExchangeValueOperation(null, "test"));
            $this->assertNull($res);
        } finally {
            $store->close();
        }
    }

    public function testCanWorkWithPrimitiveTypes(): void
    {
        $store = $this->getDocumentStore();
        try {
            $res = $store->operations()->send(new GetCompareExchangeValueOperation(null, "test"));
            $this->assertNull($res);

            $store->operations()->send(new PutCompareExchangeValueOperation("test", 5, 0));

            $res = $store->operations()->send(new GetCompareExchangeValueOperation(null, "test"));

            $this->assertNotNull($res);
            $this->assertEquals(5, $res->getValue());
        } finally {
            $store->close();
        }
    }

    public function testCanPutUniqueString(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $store->operations()->send(new PutCompareExchangeValueOperation("test", "Karmel", 0));
                $res = $store->operations()->send(new GetCompareExchangeValueOperation(null, "test"));
                $this->assertEquals("Karmel", $res->getValue());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanPutMultiDifferentValues(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user1 = new User();
            $user1->setName("Karmel");

            /** @var CompareExchangeResult $res */
            $res = $store->operations()->send(new PutCompareExchangeValueOperation("test", $user1, 0));

            $user2 = new User();
            $user2->setName("Karmel");

            /** @var CompareExchangeResult $res2 */
            $res2 = $store->operations()->send(new PutCompareExchangeValueOperation("test2", $user2, 0));

            $this->assertEquals("Karmel", $res->getValue()->getName());
            $this->assertTrue($res->isSuccessful());

            $this->assertEquals("Karmel", $res2->getValue()->getName());
            $this->assertTrue($res2->isSuccessful());
        } finally {
            $store->close();
        }
    }

    public function testCanListCompareExchange(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user1 = new User();
            $user1->setName("Karmel");
            $res1 = $store->operations()->send(new PutCompareExchangeValueOperation("test", $user1, 0));

            $user2 = new User();
            $user2->setName("Karmel");
            $res2 = $store->operations()->send(new PutCompareExchangeValueOperation("test2", $user2, 0));

            $this->assertEquals("Karmel", $res1->getValue()->getName());
            $this->assertTrue($res1->isSuccessful());

            $this->assertEquals("Karmel", $res2->getValue()->getName());
            $this->assertTrue($res2->isSuccessful());

            /** @ var Map<String, CompareExchangeValue<User>>  values */
            $values = $store->operations()->send(new GetCompareExchangeValuesOperation(User::class, "test"));
            $this->assertCount(2, $values);

            $this->assertEquals("Karmel", $values['test']->getValue()->getName());
            $this->assertEquals("Karmel", $values['test2']->getValue()->getName());
        } finally {
            $store->close();
        }
    }

    public function testCanRemoveUnique(): void
    {
        $store = $this->getDocumentStore();
        try {
            $res = $store->operations()->send(new PutCompareExchangeValueOperation("test", "Karmel", 0));

            $this->assertEquals("Karmel", $res->getValue());
            $this->assertTrue($res->isSuccessful());

            $res = $store->operations()->send(new DeleteCompareExchangeValueOperation(null,"test", $res->getIndex()));
            $this->assertTrue($res->isSuccessful());
        } finally {
            $store->close();
        }
    }

    public function testRemoveUniqueFailed(): void
    {
        $store = $this->getDocumentStore();
        try {
            $res = $store->operations()->send(new PutCompareExchangeValueOperation("test", "Karmel", 0));

            $this->assertEquals("Karmel", $res->getValue());
            $this->assertTrue($res->isSuccessful());

            $res = $store->operations()->send(new DeleteCompareExchangeValueOperation(null, "test", 0));
            $this->assertFalse($res->isSuccessful());

            $readValue = $store->operations()->send(new GetCompareExchangeValueOperation(null, "test"));
            $this->assertEquals("Karmel", $readValue->getValue());
        } finally {
            $store->close();
        }
    }

    public function testReturnCurrentValueWhenPuttingConcurrently(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user = new User();
            $user->setName("Karmel");

            $user2 = new User();
            $user2->setName("Karmel2");

            $res = $store->operations()->send(new PutCompareExchangeValueOperation("test", $user, 0));
            $res2 = $store->operations()->send(new PutCompareExchangeValueOperation("test", $user2, 0));

            $this->assertTrue($res->isSuccessful());
            $this->assertFalse($res2->isSuccessful());

            $this->assertEquals("Karmel", $res->getValue()->getName());
            $this->assertEquals("Karmel", $res2->getValue()->getName());

            $user3 = new User();
            $user3->setName("Karmel2");

            $res2 = $store->operations()->send(new PutCompareExchangeValueOperation("test", $user3, $res2->getIndex()));
            $this->assertTrue($res2->isSuccessful());

            $this->assertEquals("Karmel2", $res2->getValue()->getName());
        } finally {
            $store->close();
        }
    }

    public function testCanGetIndexValue(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user = new User();
            $user->setName("Karmel");

            $store->operations()->send(new PutCompareExchangeValueOperation("test", $user, 0));
            $res = $store->operations()->send(new GetCompareExchangeValueOperation(User::class, "test"));

            $this->assertEquals("Karmel", $res->getValue()->getName());

            $user2 = new User();
            $user2->setName("Karmel2");

            $res2 = $store->operations()->send(new PutCompareExchangeValueOperation("test", $user2, $res->getIndex()));

            $this->assertTrue($res2->isSuccessful());
            $this->assertEquals("Karmel2", $res2->getValue()->getName());
        } finally {
            $store->close();
        }
    }

    public function testCanAddMetadataToSimpleCompareExchange(): void
    {
        $store = $this->getDocumentStore();
        try {
            $str = "Test";
            $num = 123.456;
            $key = "egr/test/cmp/x/change/simple";

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $result = $session->advanced()->clusterTransaction()->createCompareExchangeValue($key, 322);
                $result->getMetadata()->put("TestString", $str);
                $result->getMetadata()->put("TestNumber", $num);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $res = $store->operations()->send(new GetCompareExchangeValueOperation(null, $key));
            $this->assertNotNull($res->getMetadata());

            $this->assertEquals(322, $res->getValue());
            $this->assertEquals($str, $res->getMetadata()->get('TestString'));
            $this->assertEquals($num, $res->getMetadata()->get('TestNumber'));

            /** @var DetailedDatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetDetailedStatisticsOperation());
            $this->assertEquals(1, $stats->getCountOfCompareExchange());
        } finally {
            $store->close();
        }
    }

    public function testCanAddMetadataToSimpleCompareExchange_array(): void
    {
        $store = $this->getDocumentStore();
        try {
            $str = "Test";
            $key = "egr/test/cmp/x/change/simple";

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $result = $session->advanced()->clusterTransaction()->createCompareExchangeValue($key, [ "a", "b", "c" ]);
                $result->getMetadata()->put("TestString", $str);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $res = $store->operations()->send(new GetCompareExchangeValueOperation(null, $key));
            $this->assertNotNull($res->getMetadata());
            $this->assertEquals(["a", "b", "c"], $res->getValue());
            $this->assertEquals($str, $res->getMetadata()->get("TestString"));
        } finally {
            $store->close();
        }
    }
}
