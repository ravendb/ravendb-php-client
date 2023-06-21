<?php

namespace tests\RavenDB\Documents\Operations\Configuration;

use RavenDB\Documents\Operations\Configuration\ClientConfiguration;
use RavenDB\Documents\Operations\Configuration\GetClientConfigurationOperation;
use RavenDB\Documents\Operations\Configuration\GetClientConfigurationResult;
use RavenDB\Documents\Operations\Configuration\PutClientConfigurationOperation;
use RavenDB\Http\ReadBalanceBehavior;
use tests\RavenDB\RemoteTestBase;

class ClientConfigurationTest extends RemoteTestBase
{
//    public function canSaveAndReadServerWideClientConfiguration(): void
//    {
//        $store = $this->getDocumentStore();
//        try {
//            $configurationToSave = new ClientConfiguration();
//            $configurationToSave->setMaxNumberOfRequestsPerSession(80);
//            $configurationToSave->setReadBalanceBehavior(ReadBalanceBehavior::fastestNode());
//            $configurationToSave->setDisabled(true);
//            $configurationToSave->setLoadBalanceBehavior(LoadBalanceBehavior::none());
//            $configurationToSave->setLoadBalancerContextSeed(0);
//
//            $saveOperation = new PutServerWideClientConfigurationOperation($configurationToSave);
//
//            $store->maintenance().server()->send($saveOperation);
//
//            $operation = new GetServerWideClientConfigurationOperation();
//            /** @var ClientConfiguration  $newConfiguration */
//            $newConfiguration = $store->maintenance()->server()->send($operation);
//
//            assertThat(newConfiguration)
//                    .isNotNull();
//
//            assertThat(newConfiguration.isDisabled())
//                    .isTrue();
//
//            assertThat(newConfiguration.getMaxNumberOfRequestsPerSession())
//                    .isEqualTo(80);
//
//            assertThat(newConfiguration.getLoadBalancerContextSeed())
//                    .isEqualTo(0);
//
//            assertThat(newConfiguration.getLoadBalanceBehavior())
//                    .isEqualTo(LoadBalanceBehavior.NONE);
//
//            assertThat(newConfiguration.getReadBalanceBehavior())
//                    .isEqualTo(ReadBalanceBehavior.FASTEST_NODE);
//        } finally {
//            $store->close();
//        }
//    }

    public function testCanHandleNoConfiguration(): void
    {
        $store = $this->getDocumentStore();
        try {
            $operation = new GetClientConfigurationOperation();
            /** @var GetClientConfigurationResult $result */
            $result = $store->maintenance()->send($operation);

            $this->assertNotNull($result->getEtag());
        } finally {
            $store->close();
        }
    }

    public function testCanSaveAndReadClientConfiguration(): void
    {
        $store = $this->getDocumentStore();
        try {

            $configurationToSave = new ClientConfiguration();
            $configurationToSave->setEtag(123);
            $configurationToSave->setMaxNumberOfRequestsPerSession(80);
            $configurationToSave->setReadBalanceBehavior(ReadBalanceBehavior::fastestNode());
            $configurationToSave->setDisabled(true);

            $saveOperation = new PutClientConfigurationOperation($configurationToSave);

            $store->maintenance()->send($saveOperation);

            $operation = new GetClientConfigurationOperation();
            /** @var GetClientConfigurationResult $result */
            $result = $store->maintenance()->send($operation);

            $this->assertNotNull($result->getEtag());

            $newConfiguration = $result->getConfiguration();

            $this->assertNotNull($newConfiguration);

            $this->assertTrue($newConfiguration->isDisabled());

            $this->assertEquals(80, $newConfiguration->getMaxNumberOfRequestsPerSession());

            $this->assertTrue($newConfiguration->getReadBalanceBehavior()->isFastestNode());
        } finally {
            $store->close();
        }
    }
}
