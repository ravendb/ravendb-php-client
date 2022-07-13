<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Utils\StringUtils;
use tests\RavenDB\RemoteTestBase;
use RavenDB\Documents\Session\SessionOptions;
use tests\RavenDB\Infrastructure\Entity\User;
use RavenDB\Documents\Session\TransactionMode;
use RavenDB\Exceptions\CompareExchangeKeyTooBigException;

class RavenDB_14989Test extends RemoteTestBase
{
    public function testShouldWork(): void
    {
        $store = $this->getDocumentStore();
        try {
            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $user = new User();
                $user->setName("egor");
                $session->advanced()->clusterTransaction()
                        ->createCompareExchangeValue(StringUtils::repeat('e', 513), $user);

                $this->expectException(CompareExchangeKeyTooBigException::class);
                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
