<?php

namespace tests\RavenDB\Test\Issues;

use DateTime;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\Refresh\ConfigureRefreshOperation;
use RavenDB\Documents\Operations\Refresh\RefreshConfiguration;
use RavenDB\Exceptions\TimeoutException;
use RavenDB\Primitives\NetISO8601Utils;
use RavenDB\Utils\DateUtils;
use RavenDB\Utils\Stopwatch;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;

class RavenDB_13735Test extends RemoteTestBase
{
    private function setupRefresh(DocumentStoreInterface $store): void
    {
        $config = new RefreshConfiguration();
        $config->setDisabled(false);
        $config->setRefreshFrequencyInSec(1);

        $store->maintenance()->send(new ConfigureRefreshOperation($config));
    }

    public function testRefreshWillUpdateDocumentChangeVector(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

        $store = $this->getDocumentStore();
        try {
            $this->setupRefresh($store);

            $expectedChangeVector = null;
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Oren");
                $session->store($user, "users/1-A");

                $hourAgo = DateUtils::addHours(DateUtils::now(), -1);
                $metadataDictionary = $session->advanced()->getMetadataFor($user);
                $metadataDictionary->put("@refresh", NetISO8601Utils::format($hourAgo, true));

                $session->saveChanges();

                $expectedChangeVector = $session->advanced()->getChangeVectorFor($user);
            } finally {
                $session->close();
            }

            $sw = Stopwatch::createStarted();

            while (true) {
                if ($sw->elapsed() > 10) { // 10 seconds
                    throw new TimeoutException();
                }

                $session = $store->openSession();
                try {
                    $user = $session->load(User::class, "users/1-A");
                    $this->assertNotNull($user);

                    if ($session->advanced()->getChangeVectorFor($user) != $expectedChangeVector) {
                        // change vector was changed - great!
                        break;
                    }
                } finally {
                    $session->close();
                }

                usleep(200);
            }
        } finally {
            $store->close();
        }
    }
}
