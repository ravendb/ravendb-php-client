<?php

namespace tests\RavenDB\Test\Cluster;

use RavenDB\Exceptions\RavenException;
use Throwable;
use Exception;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Infrastructure\Entity\User;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Documents\Session\TransactionMode;

// !status: DONE
class ClusterTransactionTest extends RemoteTestBase
{
    public function testCanCreateClusterTransactionRequest(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user1 = new User();
            $user1->setName("Karmel");

            $user3 = new User();
            $user3->setName("Indych");

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());
            $sessionOptions->setDisableAtomicDocumentWritesInClusterWideTransaction(true);

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("usernames/ayende", $user1);
                $session->store($user3, "foo/bar");
                $session->saveChanges();

                /** @var User $user */
                $user = $session->advanced()->clusterTransaction()->getCompareExchangeValue(User::class, "usernames/ayende")->getValue();
                $this->assertEquals($user1->getName(), $user->getName());

                $user = $session->load(User::class, "foo/bar");
                $this->assertEquals($user3->getName(), $user->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanDeleteCompareExchangeValue(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user1 = new User();
            $user1->setName("Karmel");

            $user3 = new User();
            $user3->setName("Indych");

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());
            $sessionOptions->setDisableAtomicDocumentWritesInClusterWideTransaction(true);

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("usernames/ayende", $user1);
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("usernames/marcin", $user3);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $compareExchangeValue = $session->advanced()->clusterTransaction()->getCompareExchangeValue(User::class, "usernames/ayende");
                $this->assertNotNull($compareExchangeValue);
                $session->advanced()->clusterTransaction()->deleteCompareExchangeValue($compareExchangeValue);

                $compareExchangeValue2 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(User::class, "usernames/marcin");
                $this->assertNotNull($compareExchangeValue2);
                $session->advanced()->clusterTransaction()->deleteCompareExchangeValue("usernames/marcin", $compareExchangeValue2->getIndex());

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $compareExchangeValue = $session->advanced()->clusterTransaction()->getCompareExchangeValue(User::class, "usernames/ayende");
                $compareExchangeValue2 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(User::class, "usernames/marcin");

                $this->assertNull($compareExchangeValue);
                $this->assertNull($compareExchangeValue2);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testTestSessionSequance(): void
    {
        $store = $this->getDocumentStore();
        try {
            $user1 = new User();
            $user1->setName("Karmel");

            $user2 = new User();
            $user2->setName("Indych");

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());
            $sessionOptions->setDisableAtomicDocumentWritesInClusterWideTransaction(true);

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("usernames/ayende", $user1);
                $session->store($user1, "users/1");
                $session->saveChanges();

                $value = $session->advanced()->clusterTransaction()->getCompareExchangeValue(User::class, "usernames/ayende");
                $value->setValue($user2);

                $session->store($user2, "users/2");
                $user1->setAge(10);
                $session->store($user1, "users/1");
                $session->saveChanges();

                $this->expectNotToPerformAssertions();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testThrowOnUnsupportedOperations(): void
    {
        $store = $this->getDocumentStore();
        try {
            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());
            $sessionOptions->setDisableAtomicDocumentWritesInClusterWideTransaction(true);

            $session = $store->openSession($sessionOptions);
            try {
                $attachmentStream = implode(array_map("chr", [1, 2, 3]));
                $session->advanced()->attachments()->store("asd", "test", $attachmentStream);

                $this->expectException(RavenException::class);
                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testThrowOnInvalidTransactionMode(): void
    {
        $user1 = new User();
        $user1->setName("Karmel");

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                try {
                    $session->advanced()->clusterTransaction()->createCompareExchangeValue("usernames/ayende", $user1);
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IllegalStateException::class, $exception);
                }

                try {
                    $session->advanced()->clusterTransaction()->deleteCompareExchangeValue("usernames/ayende", 0);
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IllegalStateException::class, $exception);
                }

            } finally {
                $session->close();
            }

            $options = new SessionOptions();
            $options->setTransactionMode(TransactionMode::clusterWide());
            $options->setDisableAtomicDocumentWritesInClusterWideTransaction(true);

            $session = $store->openSession($options);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("usernames/ayende", $user1);
                $session->advanced()->setTransactionMode(TransactionMode::singleNode());

                try {
                    $session->saveChanges();
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IllegalStateException::class, $exception);
                }

                $session->advanced()->setTransactionMode(TransactionMode::clusterWide());
                $session->saveChanges();

                $u = $session->advanced()->clusterTransaction()->getCompareExchangeValue(User::class, "usernames/ayende");
                $this->assertEquals($user1->getName(), $u->getValue()->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
