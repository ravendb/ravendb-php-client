<?php

namespace tests\RavenDB\Test\Server;

use RavenDB\Documents\Operations\Expiration\ConfigureExpirationOperation;
use RavenDB\Documents\Operations\Expiration\ConfigureExpirationOperationResult;
use RavenDB\Documents\Operations\Expiration\ExpirationConfiguration;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;

class ExpirationConfigurationTest extends RemoteTestBase
{
    public function testCanSetupExpiration(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

        $store = $this->getDocumentStore();
        try {
            $expirationConfiguration = new ExpirationConfiguration();
            $expirationConfiguration->setDeleteFrequencyInSec(5);
            $expirationConfiguration->setDisabled(false);
            $configureExpirationOperation = new ConfigureExpirationOperation($expirationConfiguration);

            /** @var ConfigureExpirationOperationResult $expirationOperationResult */
            $expirationOperationResult = $store->maintenance()->send($configureExpirationOperation);

            $this->assertNotNull($expirationOperationResult->getRaftCommandIndex());
        } finally {
            $store->close();
        }
    }
}
