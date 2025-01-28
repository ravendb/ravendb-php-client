<?php

namespace tests\RavenDB\Test\Issues;

use Exception;
use tests\RavenDB\Infrastructure\TestRunGuard;
use Throwable;
use tests\RavenDB\RemoteTestBase;
use RavenDB\Exceptions\RavenException;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Documents\Session\TransactionMode;
use tests\RavenDB\Infrastructure\Entity\Company;
use RavenDB\Documents\Operations\GetStatisticsOperation;
use RavenDB\Documents\Operations\Configuration\ClientConfiguration;
use RavenDB\Documents\Operations\Configuration\PutClientConfigurationOperation;

class RavenDB_13456Test extends RemoteTestBase
{
    public function testCanChangeIdentityPartsSeparator(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company1 = new Company();
                $session->store($company1);

                $this->assertStringStartsWith('companies/1-A', $company1->getId());

                $company2 = new Company();
                $session->store($company2);

                $this->assertStringStartsWith('companies/2-A', $company2->getId());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $session->store($company1, "companies/");

                $company2 = new Company();
                $session->store($company2, "companies|");

                $session->saveChanges();

                $this->assertStringStartsWith('companies/000000000', $company1->getId());
                $this->assertEquals('companies/1', $company2->getId());

            } finally {
                $session->close();
            }

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("company:", new Company());
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("company|", new Company());
                try {
                    $session->saveChanges();
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(RavenException::class, $exception);
                    $this->assertStringContainsString("Document id company| cannot end with '|' or '/' as part of cluster transaction", $exception->getMessage());
                };
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("company/", new Company());
                try {
                    $session->saveChanges();
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(RavenException::class, $exception);
                    $this->assertStringContainsString("Document id company/ cannot end with '|' or '/' as part of cluster transaction", $exception->getMessage());
                };
            } finally {
                $session->close();
            }

            $clientConfiguration = new ClientConfiguration();
            $clientConfiguration->setIdentityPartsSeparator(':');

            $store->maintenance()->send(new PutClientConfigurationOperation($clientConfiguration));

            $store->maintenance()->send(new GetStatisticsOperation()); // forcing client configuration update

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $session->store($company1);

                $this->assertStringStartsWith('companies:3-A', $company1->getId());

                $company2 = new Company();
                $session->store($company2);

                $this->assertStringStartsWith('companies:4-A', $company2->getId());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $session->store($company1, "companies:");

                $company2 = new Company();
                $session->store($company2, "companies|");

                $session->saveChanges();

                $this->assertStringStartsWith('companies:000000000', $company1->getId());
                $this->assertEquals('companies:2', $company2->getId());
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("company:", new Company());

                try {
                    $session->saveChanges();
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(RavenException::class, $exception);
                    $this->assertStringContainsString("Document id company: cannot end with '|' or ':' as part of cluster transaction", $exception->getMessage());
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("company|", new Company());

                try {
                    $session->saveChanges();
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(RavenException::class, $exception);
                    $this->assertStringContainsString("Document id company| cannot end with '|' or ':' as part of cluster transaction", $exception->getMessage());
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("company/", new Company());

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $secondClientConfiguration = new ClientConfiguration();
            $secondClientConfiguration->setIdentityPartsSeparator(null);
            $store->maintenance()->send(new PutClientConfigurationOperation($secondClientConfiguration));

            $store->maintenance()->send(new GetStatisticsOperation()); // forcing client configuration update

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $session->store($company1);

                $this->assertStringStartsWith('companies/5-A', $company1->getId());

                $company2 = new Company();
                $session->store($company2);

                $this->assertStringStartsWith('companies/6-A', $company2->getId());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $session->store($company1, "companies/");

                $company2 = new Company();
                $session->store($company2, "companies|");

                $session->saveChanges();

                $this->assertStringStartsWith('companies/000000000', $company1->getId());
                $this->assertEquals('companies/3', $company2->getId());
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $company = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Company::class, "company:");
                $company->getValue()->setName("HR");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("company|", new Company());

                try {
                    $session->saveChanges();
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(RavenException::class, $exception);
                    $this->assertStringContainsString("Document id company| cannot end with '|' or '/' as part of cluster transaction", $exception->getMessage());
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("company/", new Company());

                try {
                    $session->saveChanges();
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(RavenException::class, $exception);
                    $this->assertStringContainsString("Document id company/ cannot end with '|' or '/' as part of cluster transaction", $exception->getMessage());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
