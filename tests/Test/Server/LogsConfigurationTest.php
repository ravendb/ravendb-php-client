<?php

namespace tests\RavenDB\Test\Server;

use RavenDB\ServerWide\Operations\Logs\GetLogsConfigurationOperation;
use RavenDB\ServerWide\Operations\Logs\GetLogsConfigurationResult;
use RavenDB\ServerWide\Operations\Logs\LogMode;
use RavenDB\ServerWide\Operations\Logs\SetLogsConfigurationOperation;
use RavenDB\ServerWide\Operations\Logs\SetLogsConfigurationParameters;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;

class LogsConfigurationTest extends RemoteTestBase
{
    public function testCanGetAndSetLogging(): void
    {
        TestRunGuard::disableTestForRaven7AndLater($this);

        $store = $this->getDocumentStore();
        try {
            try {
                $getOperation = new GetLogsConfigurationOperation();

                /** @var GetLogsConfigurationResult $logsConfig */
                $logsConfig = $store->maintenance()->server()->send($getOperation);

                $this->assertTrue($logsConfig->getCurrentMode()->isOperations());
                $this->assertTrue($logsConfig->getMode()->isOperations());

                // now try to set mode to operations and info
                $parameters = new SetLogsConfigurationParameters();
                $parameters->setMode(LogMode::information());
                $setOperation = new SetLogsConfigurationOperation($parameters);

                $store->maintenance()->server()->send($setOperation);

                $getOperation = new GetLogsConfigurationOperation();

                /** @var GetLogsConfigurationResult $logsConfig */
                $logsConfig = $store->maintenance()->server()->send($getOperation);

                $this->assertTrue($logsConfig->getCurrentMode()->isInformation());
                $this->assertTrue($logsConfig->getMode()->isOperations());
            } finally {
                // try to clean up

                $parameters = new SetLogsConfigurationParameters();
                $parameters->setMode(LogMode::operations());
                $setOperation = new SetLogsConfigurationOperation($parameters);
                $store->maintenance()->server()->send($setOperation);
            }
        } finally {
            $store->close();
        }
    }
}
