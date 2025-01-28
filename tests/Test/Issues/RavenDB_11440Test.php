<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Exceptions\IllegalStateException;
use RavenDB\ServerWide\Operations\Logs\GetLogsConfigurationOperation;
use RavenDB\ServerWide\Operations\Logs\GetLogsConfigurationResult;
use RavenDB\ServerWide\Operations\Logs\LogMode;
use RavenDB\ServerWide\Operations\Logs\SetLogsConfigurationOperation;
use RavenDB\ServerWide\Operations\Logs\SetLogsConfigurationParameters;
use RavenDB\Type\Duration;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;

class RavenDB_11440Test extends RemoteTestBase
{
    public function testCanGetLogsConfigurationAndChangeMode(): void
    {
        TestRunGuard::disableTestForRaven7AndLater($this);

        $store = $this->getDocumentStore();
        try {
            /** @var GetLogsConfigurationResult $configuration */
            $configuration = $store->maintenance()->server()->send(new GetLogsConfigurationOperation());

            try {
                $modeToSet = null;

                switch ($configuration->getCurrentMode()->getValue()) {
                    case LogMode::NONE:
                        $modeToSet = LogMode::information();
                        break;
                    case LogMode::OPERATIONS:
                        $modeToSet = LogMode::information();
                        break;
                    case LogMode::INFORMATION:
                        $modeToSet = LogMode::none();
                        break;
                    default:
                        throw new IllegalStateException("Invalid mode: " . $configuration->getCurrentMode());
                }

                $time = Duration::ofDays(1000);

                $parameters = new SetLogsConfigurationParameters();
                $parameters->setMode($modeToSet);
                $parameters->setRetentionTime($time);
                $setLogsOperation = new SetLogsConfigurationOperation($parameters);
                $store->maintenance()->server()->send($setLogsOperation);

                /** @var GetLogsConfigurationResult $configuration2 */
                $configuration2 = $store->maintenance()->server()->send(new GetLogsConfigurationOperation());

                $this->assertEquals($modeToSet, $configuration2->getCurrentMode());
                $this->assertEquals($time->toString(), $configuration2->getRetentionTime()->toString());
                $this->assertEquals($configuration->getMode(), $configuration2->getMode());
                $this->assertEquals($configuration->getPath(), $configuration2->getPath());
                $this->assertEquals($configuration->isUseUtcTime(), $configuration2->isUseUtcTime());
            } finally {
                $parameters = new SetLogsConfigurationParameters();
                $parameters->setMode($configuration->getCurrentMode());
                $parameters->setRetentionTime($configuration->getRetentionTime());

                $store->maintenance()->server()->send(new SetLogsConfigurationOperation($parameters));
            }
        } finally {
            $store->close();
        }
    }
}
