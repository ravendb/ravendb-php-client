<?php

namespace tests\RavenDB\Test\Client\TimeSeries;

use DateTime;
use Exception;
use RavenDB\Documents\Operations\TimeSeries\ConfigureRawTimeSeriesPolicyOperation;
use RavenDB\Documents\Operations\TimeSeries\ConfigureTimeSeriesOperation;
use RavenDB\Documents\Operations\TimeSeries\ConfigureTimeSeriesPolicyOperation;
use RavenDB\Documents\Operations\TimeSeries\ConfigureTimeSeriesValueNamesOperation;
use RavenDB\Documents\Operations\TimeSeries\ConfigureTimeSeriesValueNamesParameters;
use RavenDB\Documents\Operations\TimeSeries\RawTimeSeriesPolicy;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesCollectionConfiguration;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesCollectionConfigurationMap;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesConfiguration;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesPolicy;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Primitives\TimeValue;
use RavenDB\ServerWide\GetDatabaseRecordOperation;
use RavenDB\Type\Duration;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\Infrastructure\DisableOnPullRequestCondition;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class TimeSeriesConfigurationTest extends RemoteTestBase
{
    public function testSerialization(): void
    {
        $mapper = JsonExtensions::getDefaultMapper();

        $this->assertEquals("{\"Value\":7200,\"Unit\":\"Second\"}", $mapper->serialize(TimeValue::ofHours(2), 'json'));
    }

    public function testDeserialization(): void
    {
        $mapper = JsonExtensions::getDefaultMapper();

        $timeValue = $mapper->deserialize("{\"Value\":7200,\"Unit\":\"Second\"}", TimeValue::class, 'json');
        $this->assertTrue($timeValue->getUnit()->isSecond());
        $this->assertEquals(7200, $timeValue->getValue());

        $timeValue = $mapper->deserialize("{\"Value\":2,\"Unit\":\"Month\"}", TimeValue::class, 'json');
        $this->assertTrue($timeValue->getUnit()->isMonth());
        $this->assertEquals(2, $timeValue->getValue());

        $timeValue = $mapper->deserialize("{\"Value\":0,\"Unit\":\"None\"}", TimeValue::class, 'json');
        $this->assertTrue($timeValue->getUnit()->isNone());
        $this->assertEquals(0, $timeValue->getValue());
    }

    public function testCanConfigureTimeSeries(): void
    {
        DisableOnPullRequestCondition::evaluateExecutionCondition($this);

        $store = $this->getDocumentStore();
        try {
            $config = new TimeSeriesConfiguration();
            $store->maintenance()->send(new ConfigureTimeSeriesOperation($config));

            $config->setCollections([]);
            $store->maintenance()->send(new ConfigureTimeSeriesOperation($config));

            $config->getCollections()["Users"] = new TimeSeriesCollectionConfiguration();
            $store->maintenance()->send(new ConfigureTimeSeriesOperation($config));

            $users = $config->getCollections()["Users"];
            $users->setPolicies([
                new TimeSeriesPolicy("ByHourFor12Hours", TimeValue::ofHours(1), TimeValue::ofHours(48)),
                new TimeSeriesPolicy("ByMinuteFor3Hours", TimeValue::ofMinutes(1), TimeValue::ofMinutes(180)),
                new TimeSeriesPolicy("BySecondFor1Minute", TimeValue::ofSeconds(1), TimeValue::ofSeconds(60)),
                new TimeSeriesPolicy("ByMonthFor1Year", TimeValue::ofMonths(1), TimeValue::ofYears(1)),
                new TimeSeriesPolicy("ByYearFor3Years", TimeValue::ofYears(1), TimeValue::ofYears(3)),
                new TimeSeriesPolicy("ByDayFor1Month", TimeValue::ofDays(1), TimeValue::ofMonths(1))
            ]);

            $store->maintenance()->send(new ConfigureTimeSeriesOperation($config));

            $users->setRawPolicy(new RawTimeSeriesPolicy(TimeValue::ofHours(96)));
            $store->maintenance()->send(new ConfigureTimeSeriesOperation($config));

            $updated = $store->maintenance()->server()
                ->send(new GetDatabaseRecordOperation($store->getDatabase()))
                ->getTimeSeries();

            $collection = $updated->getCollections()["Users"];
            $policies = $collection->getPolicies();
            $this->assertCount(6, $policies);

            $this->assertEquals(TimeValue::ofSeconds(60), $policies[0]->getRetentionTime());
            $this->assertEquals(TimeValue::ofSeconds(1), $policies[0]->getAggregationTime());

            $this->assertEquals(TimeValue::ofMinutes(180), $policies[1]->getRetentionTime());
            $this->assertEquals(TimeValue::ofMinutes(1), $policies[1]->getAggregationTime());

            $this->assertEquals(TimeValue::ofHours(48), $policies[2]->getRetentionTime());
            $this->assertEquals(TimeValue::ofHours(1), $policies[2]->getAggregationTime());

            $this->assertEquals(TimeValue::ofMonths(1), $policies[3]->getRetentionTime());
            $this->assertEquals(TimeValue::ofDays(1), $policies[3]->getAggregationTime());

            $this->assertEquals(TimeValue::ofYears(1), $policies[4]->getRetentionTime());
            $this->assertEquals(TimeValue::ofMonths(1), $policies[4]->getAggregationTime());

            $this->assertEquals(TimeValue::ofYears(3), $policies[5]->getRetentionTime());
            $this->assertEquals(TimeValue::ofYears(1), $policies[5]->getAggregationTime());
        } finally {
            $store->close();
        }
    }

    public function testNotValidConfigureShouldThrow(): void
    {
        $store = $this->getDocumentStore();
        try {
            $timeSeriesCollectionConfiguration = new TimeSeriesCollectionConfiguration();
            $timeSeriesCollectionConfiguration->setRawPolicy(
                new RawTimeSeriesPolicy(TimeValue::ofMonths(1))
            );
            $timeSeriesCollectionConfiguration->setPolicies(
                [
                    new TimeSeriesPolicy("By30DaysFor5Years", TimeValue::ofDays(30), TimeValue::ofYears(5))
                ]);

            $collectionsConfig = [];
            $collectionsConfig["Users"] = $timeSeriesCollectionConfiguration;

            $config = new TimeSeriesConfiguration();
            $config->setCollections($collectionsConfig);

            try {
                $store->maintenance()->send(new ConfigureTimeSeriesOperation($config));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("month might have different number of days", $exception->getMessage());
            }


            $timeSeriesCollectionConfiguration = new TimeSeriesCollectionConfiguration();
            $timeSeriesCollectionConfiguration->setRawPolicy(new RawTimeSeriesPolicy(TimeValue::ofMonths(12)));
            $timeSeriesCollectionConfiguration->setPolicies([
                new TimeSeriesPolicy(
                    "By365DaysFor5Years",
                    TimeValue::ofSeconds(365 * 24 * 3600),
                    TimeValue::ofYears(5))
            ]);

            $collectionsConfig = [];
            $collectionsConfig["Users"] = $timeSeriesCollectionConfiguration;

            $config2 = new TimeSeriesConfiguration();
            $config2->setCollections($collectionsConfig);

            try {
                $store->maintenance()->send(new ConfigureTimeSeriesOperation($config2));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("month might have different number of days", $exception->getMessage());
            }

            $timeSeriesCollectionConfiguration = new TimeSeriesCollectionConfiguration();
            $timeSeriesCollectionConfiguration->setRawPolicy(new RawTimeSeriesPolicy(TimeValue::ofMonths(1)));
            $timeSeriesCollectionConfiguration->setPolicies([
                new TimeSeriesPolicy("By27DaysFor1Year", TimeValue::ofDays(27), TimeValue::ofYears(1)),
                new TimeSeriesPolicy("By364DaysFor5Years", TimeValue::ofDays(364), TimeValue::ofYears(5))
            ]);

            $collectionsConfig = [];
            $collectionsConfig["Users"] = $timeSeriesCollectionConfiguration;

            $config3 = new TimeSeriesConfiguration();
            $config3->setCollections($collectionsConfig);

            try {
                $store->maintenance()->send(new ConfigureTimeSeriesOperation($config3));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("The aggregation time of the policy 'By364DaysFor5Years' (364 days) must be divided by the aggregation time of 'By27DaysFor1Year' (27 days) without a remainder", $exception->getMessage());
            }
        } finally {
            $store->close();
        }
    }

    public function testCanExecuteSimpleRollup(): void
    {
        DisableOnPullRequestCondition::evaluateExecutionCondition($this);

        $store = $this->getDocumentStore();
        try {
            $p1 = new TimeSeriesPolicy("BySecond", TimeValue::ofSeconds(1));
            $p2 = new TimeSeriesPolicy("By2Seconds", TimeValue::ofSeconds(2));
            $p3 = new TimeSeriesPolicy("By4Seconds", TimeValue::ofSeconds(4));

            $collectionConfig = new TimeSeriesCollectionConfiguration();
            $collectionConfig->setPolicies([$p1, $p2, $p3]);

            $config = new TimeSeriesConfiguration();
            $config->setCollections(TimeSeriesCollectionConfigurationMap::fromArray(["Users" => $collectionConfig]));

            $config->setPolicyCheckFrequency(Duration::ofSeconds(1));

            $store->maintenance()->send(new ConfigureTimeSeriesOperation($config));

            $baseLine = DateUtils::addDays(DateUtils::truncateDayOfMonth(new DateTime()), -1);

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Karmel");
                $session->store($user, "users/karmel");

                for ($i = 0; $i < 100; $i++) {
                    $session->timeSeriesFor("users/karmel", "Heartrate")
                        ->append(DateUtils::addMilliseconds($baseLine, 400 * $i), 29.0 * $i, "watches/fitbit");
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            // wait for rollups to run
            usleep(1200000);

            $session = $store->openSession();
            try {
                $ts = $session->timeSeriesFor("users/karmel", "Heartrate")
                    ->get();

                $diff = $ts[0]->getTimestamp()->diff($ts[count($ts) - 1]->getTimestamp());
                $tsMillis = DateUtils::intervalToMilliseconds($diff);

                $ts1 = $session->timeSeriesFor("users/karmel", $p1->getTimeSeriesName("Heartrate"))
                    ->get();

                $diff1 = $ts1[0]->getTimestamp()->diff($ts1[count($ts1) - 1]->getTimestamp());
                $ts1Millis = DateUtils::intervalToMilliseconds($diff1);

                $this->assertEquals($tsMillis - 600, $ts1Millis);

                $ts2 = $session->timeSeriesFor("users/karmel", $p2->getTimeSeriesName("Heartrate"))
                    ->get();
                $this->assertCount(count($ts1) / 2, $ts2);

                $ts3 = $session->timeSeriesFor("users/karmel", $p3->getTimeSeriesName("Heartrate"))
                    ->get();
                $this->assertCount(count($ts1) / 4, $ts3);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanConfigureTimeSeries2(): void
    {
        DisableOnPullRequestCondition::evaluateExecutionCondition($this);

        $store = $this->getDocumentStore();
        try  {
            $collectionName = "Users";

            $p1 = new TimeSeriesPolicy("BySecondFor1Minute", TimeValue::ofSeconds(1), TimeValue::ofSeconds(60));
            $p2 = new TimeSeriesPolicy("ByMinuteFor3Hours",TimeValue::ofMinutes(1), TimeValue::ofMinutes(180));
            $p3 = new TimeSeriesPolicy("ByHourFor12Hours",TimeValue::ofHours(1), TimeValue::ofHours(48));
            $p4 = new TimeSeriesPolicy("ByDayFor1Month",TimeValue::ofDays(1), TimeValue::ofMonths(1));
            $p5 = new TimeSeriesPolicy("ByMonthFor1Year",TimeValue::ofMonths(1), TimeValue::ofYears(1));
            $p6 = new TimeSeriesPolicy("ByYearFor3Years",TimeValue::ofYears(1), TimeValue::ofYears(3));

            $policies = [ $p1, $p2, $p3, $p4, $p5, $p6];

            foreach ($policies as $policy) {
                $store->maintenance()->send(new ConfigureTimeSeriesPolicyOperation($collectionName, $policy));
            }

            $store->maintenance()->send(new ConfigureRawTimeSeriesPolicyOperation($collectionName, new RawTimeSeriesPolicy(TimeValue::ofHours(96))));
            $parameters = new ConfigureTimeSeriesValueNamesParameters();
            $parameters->setCollection($collectionName);
            $parameters->setTimeSeries("HeartRate");
            $parameters->setValueNames([ "HeartRate" ]);
            $parameters->setUpdate(true);

            $nameConfig = new ConfigureTimeSeriesValueNamesOperation($parameters);
            $store->maintenance()->send($nameConfig);

            $updated = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($store->getDatabase()))->getTimeSeries();
            $collection = $updated->getCollections()[$collectionName];
            $policies = $collection->getPolicies();

            $this->assertCount(6, $policies);

            $this->assertEquals(TimeValue::ofSeconds(60), $policies[0]->getRetentionTime());
            $this->assertEquals(TimeValue::ofSeconds(1), $policies[0]->getAggregationTime());

            $this->assertEquals(TimeValue::ofMinutes(180), $policies[1]->getRetentionTime());
            $this->assertEquals(TimeValue::ofMinutes(1), $policies[1]->getAggregationTime());

            $this->assertEquals(TimeValue::ofHours(48), $policies[2]->getRetentionTime());
            $this->assertEquals(TimeValue::ofHours(1), $policies[2]->getAggregationTime());

            $this->assertEquals(TimeValue::ofMonths(1), $policies[3]->getRetentionTime());
            $this->assertEquals(TimeValue::ofDays(1), $policies[3]->getAggregationTime());

            $this->assertEquals(TimeValue::ofYears(1), $policies[4]->getRetentionTime());
            $this->assertEquals(TimeValue::ofMonths(1), $policies[4]->getAggregationTime());

            $this->assertEquals(TimeValue::ofYears(3), $policies[5]->getRetentionTime());
            $this->assertEquals(TimeValue::ofYears(1), $policies[5]->getAggregationTime());

            $this->assertNotNull($updated->getNamedValues());

            $this->assertCount(1, $updated->getNamedValues());

            $mapper = $updated->getNames($collectionName, "heartrate");
            $this->assertNotNull($mapper);
            $this->assertCount(1, $mapper);
            $this->assertContains("HeartRate", $mapper);
        } finally {
            $store->close();
        }
    }

    public function testCanConfigureTimeSeries3(): void
    {
        DisableOnPullRequestCondition::evaluateExecutionCondition($this);
        $store = $this->getDocumentStore();
        try  {
            $store->timeSeries()->setPolicy(User::class, "By15SecondsFor1Minute", TimeValue::ofSeconds(15), TimeValue::ofSeconds(60));
            $store->timeSeries()->setPolicy(User::class, "ByMinuteFor3Hours", TimeValue::ofMinutes(1), TimeValue::ofMinutes(180));
            $store->timeSeries()->setPolicy(User::class, "ByHourFor12Hours", TimeValue::ofHours(1), TimeValue::ofHours(48));
            $store->timeSeries()->setPolicy(User::class, "ByDayFor1Month", TimeValue::ofDays(1), TimeValue::ofMonths(1));
            $store->timeSeries()->setPolicy(User::class, "ByMonthFor1Year", TimeValue::ofMonths(1), TimeValue::ofYears(1));
            $store->timeSeries()->setPolicy(User::class, "ByYearFor3Years", TimeValue::ofYears(1), TimeValue::ofYears(3));

            $updated = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($store->getDatabase()))->getTimeSeries();
            $collection = $updated->getCollections()["Users"];
            $policies = $collection->getPolicies();

            $this->assertCount(6, $policies);

            $this->assertEquals(TimeValue::ofSeconds(60), $policies[0]->getRetentionTime());
            $this->assertEquals(TimeValue::ofSeconds(15), $policies[0]->getAggregationTime());

            $this->assertEquals(TimeValue::ofMinutes(180), $policies[1]->getRetentionTime());
            $this->assertEquals(TimeValue::ofMinutes(1), $policies[1]->getAggregationTime());

            $this->assertEquals(TimeValue::ofHours(48), $policies[2]->getRetentionTime());
            $this->assertEquals(TimeValue::ofHours(1), $policies[2]->getAggregationTime());

            $this->assertEquals(TimeValue::ofMonths(1), $policies[3]->getRetentionTime());
            $this->assertEquals(TimeValue::ofDays(1), $policies[3]->getAggregationTime());

            $this->assertEquals(TimeValue::ofYears(1), $policies[4]->getRetentionTime());
            $this->assertEquals(TimeValue::ofMonths(1), $policies[4]->getAggregationTime());

            $this->assertEquals(TimeValue::ofYears(3), $policies[5]->getRetentionTime());
            $this->assertEquals(TimeValue::ofYears(1), $policies[5]->getAggregationTime());

            try {
                $store->timeSeries()->removePolicy(User::class, "ByMinuteFor3Hours");

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("System.InvalidOperationException: The policy 'By15SecondsFor1Minute' has a retention time of '60 seconds' but should be aggregated by policy 'ByHourFor12Hours' with the aggregation time frame of 60 minutes", $exception->getMessage());
            }

            try {
                $store->timeSeries()->setRawPolicy(User::class, TimeValue::ofSeconds(10));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("System.InvalidOperationException: The policy 'rawpolicy' has a retention time of '10 seconds' but should be aggregated by policy 'By15SecondsFor1Minute' with the aggregation time frame of 15 seconds", $exception->getMessage());
            }

            $store->timeSeries()->setRawPolicy(User::class, TimeValue::ofMinutes(120));
            $store->timeSeries()->setPolicy(User::class, "By15SecondsFor1Minute", TimeValue::ofSeconds(30), TimeValue::ofSeconds(120));

            $updated = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($store->getDatabase()))->getTimeSeries();
            $collection = $updated->getCollections()["Users"];
            $policies = $collection->getPolicies();

            $this->assertCount(6, $policies);
            $this->assertEquals(TimeValue::ofSeconds(120), $policies[0]->getRetentionTime());
            $this->assertEquals(TimeValue::ofSeconds(30), $policies[0]->getAggregationTime());

            $store->timeSeries()->removePolicy(User::class, "By15SecondsFor1Minute");

            $updated = $store->maintenance()->server()->send(new GetDatabaseRecordOperation($store->getDatabase()))->getTimeSeries();
            $collection = $updated->getCollections()["Users"];
            $policies = $collection->getPolicies();

            $this->assertCount(5, $policies);

            $store->timeSeries()->removePolicy(User::class, RawTimeSeriesPolicy::POLICY_STRING);
        } finally {
            $store->close();
        }
    }
}
