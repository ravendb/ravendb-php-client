<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14006Test;

use tests\RavenDB\RemoteTestBase;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Documents\Session\TransactionMode;
use RavenDB\Documents\Session\QueryStatistics;
use tests\RavenDB\Infrastructure\Orders\Company;
use tests\RavenDB\Infrastructure\Orders\Address;
use tests\RavenDB\Infrastructure\Orders\Employee;

class RavenDB_14006Test extends RemoteTestBase
{
    public function testCompareExchangeValueTrackingInSession(): void
    {
        $store = $this->getDocumentStore();
        try {
            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $company = new Company();
                $company->setId("companies/1");
                $company->setExternalId("companies/cf");
                $company->setName("CF");

                $session->store($company);

                $numberOfRequests = $session->advanced()->getNumberOfRequests();

                $address = new Address();
                $address->setCity("Torun");
                $session->advanced()
                    ->clusterTransaction()
                    ->createCompareExchangeValue($company->getExternalId(), $address);

                $this->assertEquals($numberOfRequests, $session->advanced()->getNumberOfRequests());

                $value1 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $company->getExternalId());

                $this->assertEquals($numberOfRequests, $session->advanced()->getNumberOfRequests());

                $this->assertEquals($address, $value1->getValue());

                $this->assertEquals($company->getExternalId(), $value1->getKey());
                $this->assertEquals(0, $value1->getIndex());

                $session->saveChanges();

                $this->assertEquals($numberOfRequests + 1, $session->advanced()->getNumberOfRequests());

                $this->assertEquals($address, $value1->getValue());
                $this->assertEquals($company->getExternalId(), $value1->getKey());
                $this->assertGreaterThan(0, $value1->getIndex());

                $value2 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $company->getExternalId());

                $this->assertEquals($numberOfRequests + 1, $session->advanced()->getNumberOfRequests());
                $this->assertSame($value1, $value2);

                $session->saveChanges();

                $this->assertEquals($numberOfRequests + 1, $session->advanced()->getNumberOfRequests());

                $session->advanced()->clear();

                $value3 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $company->getExternalId());
                $this->assertNotSame($value2, $value3);
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $address = new Address();
                $address->setCity("Hadera");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/hr", $address);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $numberOfRequests = $session->advanced()->getNumberOfRequests();

                $value1 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, "companies/cf");

                $this->assertEquals($numberOfRequests + 1, $session->advanced()->getNumberOfRequests());

                $value2 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, "companies/hr");

                $this->assertEquals($numberOfRequests + 2, $session->advanced()->getNumberOfRequests());

                // Map<String, CompareExchangeValue<Address>>
                $values = $session->advanced()->clusterTransaction()->getCompareExchangeValues(Address::class, ["companies/cf", "companies/hr"]);

                $this->assertEquals($numberOfRequests + 2, $session->advanced()->getNumberOfRequests());

                $this->assertCount(2, $values);

                $this->assertEquals($value1, $values[$value1->getKey()]);
                $this->assertEquals($value2, $values[$value2->getKey()]);

                $values = $session->advanced()->clusterTransaction()
                    ->getCompareExchangeValues(
                        Address::class,
                        ["companies/cf", "companies/hr", "companies/hx"]
                    );

                $this->assertEquals($numberOfRequests + 3, $session->advanced()->getNumberOfRequests());
                $this->assertCount(3, $values);
                $this->assertEquals($value1, $values[$value1->getKey()]);
                $this->assertEquals($value2, $values[$value2->getKey()]);

                $value3 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, "companies/hx");
                $this->assertEquals($numberOfRequests + 3, $session->advanced()->getNumberOfRequests());

                $this->assertNull($value3);
                $this->assertNull($values["companies/hx"]);

                $session->saveChanges();

                $this->assertEquals($numberOfRequests + 3, $session->advanced()->getNumberOfRequests());

                $address = new Address();
                $address->setCity("Bydgoszcz");

                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/hx", $address);

                $session->saveChanges();

                $this->assertEquals($numberOfRequests + 4, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCompareExchangeValueTrackingInSession_NoTracking(): void
    {
        $store = $this->getDocumentStore();
        try {
            $company = new Company();
            $company->setId("companies/1");
            $company->setExternalId("companies/cf");
            $company->setName("CF");

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $session->store($company);

                $address = new Address();
                $address->setCity("Torun");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue($company->getExternalId(), $address);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $sessionOptionsNoTracking = new SessionOptions();
            $sessionOptionsNoTracking->setNoTracking(true);
            $sessionOptionsNoTracking->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptionsNoTracking);
            try {
                $numberOfRequests = $session->advanced()->getNumberOfRequests();
                $value1           = $session->advanced()->clusterTransaction()->getCompareExchangeValue(
                    Address::class, $company->getExternalId()
                );

                $this->assertEquals($numberOfRequests + 1, $session->advanced()->getNumberOfRequests());

                $value2 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(
                    Address::class, $company->getExternalId()
                );

                $this->assertEquals($numberOfRequests + 2, $session->advanced()->getNumberOfRequests());

                $this->assertNotSame($value1, $value2);

                $value3 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(
                    Address::class, $company->getExternalId()
                );

                $this->assertEquals($numberOfRequests + 3, $session->advanced()->getNumberOfRequests());
                $this->assertNotSame($value2, $value3);
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptionsNoTracking);
            try {
                $numberOfRequests = $session->advanced()->getNumberOfRequests();

                $value1 = $session
                    ->advanced()
                    ->clusterTransaction()
                    ->getCompareExchangeValues(Address::class, $company->getExternalId());

                $this->assertEquals($numberOfRequests + 1, $session->advanced()->getNumberOfRequests());

                $value2 = $session
                    ->advanced()
                    ->clusterTransaction()
                    ->getCompareExchangeValues(Address::class, $company->getExternalId());

                $this->assertEquals($numberOfRequests + 2, $session->advanced()->getNumberOfRequests());

                $this->assertNotSame($value1[$company->getExternalId()], $value2[$company->getExternalId()]);

                $value3 = $session
                    ->advanced()
                    ->clusterTransaction()
                    ->getCompareExchangeValues(Address::class, $company->getExternalId());

                $this->assertEquals($numberOfRequests + 3, $session->advanced()->getNumberOfRequests());
                $this->assertNotSame($value2[$company->getExternalId()], $value3[$company->getExternalId()]);
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptionsNoTracking);
            try {
                $numberOfRequests = $session->advanced()->getNumberOfRequests();

                $value1 = $session
                    ->advanced()
                    ->clusterTransaction()
                    ->getCompareExchangeValues(Address::class, [ $company->getExternalId() ]);

                $this->assertEquals($numberOfRequests + 1, $session->advanced()->getNumberOfRequests());

                $value2 = $session
                    ->advanced()
                    ->clusterTransaction()
                    ->getCompareExchangeValues(Address::class, [ $company->getExternalId() ]);

                $this->assertEquals($numberOfRequests + 2, $session->advanced()->getNumberOfRequests());
                $this->assertNotSame($value1[$company->getExternalId()], $value2[$company->getExternalId()]);

                $value3 = $session
                    ->advanced()
                    ->clusterTransaction()
                    ->getCompareExchangeValues(Address::class, [ $company->getExternalId() ]);

                $this->assertEquals($numberOfRequests + 3, $session->advanced()->getNumberOfRequests());
                $this->assertNotSame($value2[$company->getExternalId()], $value3[$company->getExternalId()]);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanUseCompareExchangeValueIncludesInLoad(): void
    {
        $store = $this->getDocumentStore();
        try {
            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $employee = new Employee();
                $employee->setId("employees/1");
                $employee->setNotes(["companies/cf", "companies/hr"]);

                $session->store($employee);

                $company = new Company();
                $company->setId("companies/1");
                $company->setExternalId("companies/cf");
                $company->setName("CF");
                $session->store($company);

                $address1 = new Address();
                $address1->setCity("Torun");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/cf", $address1);

                $address2 = new Address();
                $address2->setCity("Hadera");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/hr", $address2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                /** @var Company $company1 */
                $company1 = $session->load(Company::class, "companies/1",
                    function ($i) {
                        $i->includeCompareExchangeValue('externalId');
                    }
                );

                $numberOfRequests = $session->advanced()->getNumberOfRequests();

                $value1 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $company1->getExternalId());

                $this->assertEquals($numberOfRequests, $session->advanced()->getNumberOfRequests());

                $this->assertNotNull($value1);
                $this->assertGreaterThan(0, $value1->getIndex());
                $this->assertEquals($company1->getExternalId(), $value1->getKey());

                $this->assertNotNull($value1->getValue());
                $this->assertEquals('Torun', $value1->getValue()->getCity());

                $company2 = $session->load(Company::class, "companies/1", function ($i) {
                    $i->includeCompareExchangeValue("externalId");
                });

                $this->assertEquals($numberOfRequests, $session->advanced()->getNumberOfRequests());
                $this->assertEquals($company1, $company2);

                /** @var Employee $employee1 */
                $employee1 = $session->load(Employee::class, "employees/1", function ($i) {
                    $i->includeCompareExchangeValue("notes");
                });

                $this->assertEquals($numberOfRequests + 1, $session->advanced()->getNumberOfRequests());

                $value2 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $employee1->getNotes()[0]);
                $value3 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $employee1->getNotes()[1]);

                $this->assertEquals($numberOfRequests + 1, $session->advanced()->getNumberOfRequests());
                $this->assertSame($value1, $value2);
                $this->assertNotSame($value2, $value3);

                $values = $session->advanced()->clusterTransaction()->getCompareExchangeValues(Address::class, $employee1->getNotes());

                $this->assertEquals($numberOfRequests + 1, $session->advanced()->getNumberOfRequests());

                $this->assertEquals(2, count($values));

                $this->assertSame($value2, $values[$value2->getKey()]);
                $this->assertSame($value3, $values[$value3->getKey()]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanUseCompareExchangeValueIncludesInQueries_Dynamic(): void
    {
        $store = $this->getDocumentStore();
        try {
            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $employee = new Employee();
                $employee->setId("employees/1");
                $employee->setNotes(["companies/cf", "companies/hr"]);
                $session->store($employee);

                $company = new Company();
                $company->setId("companies/1");
                $company->setExternalId("companies/cf");
                $company->setName("CF");
                $session->store($company);

                $address1 = new Address();
                $address1->setCity("Torun");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/cf", $address1);

                $address2 = new Address();
                $address2->setCity("Hadera");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/hr", $address2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $queryStats = new QueryStatistics();
                $companies = $session->query(get_class($company))
                        ->statistics($queryStats)
                        ->include(function($b) {
                            $b->includeCompareExchangeValue("externalId");
                        })
                        ->toList();

                $this->assertCount(1, $companies);

                $this->assertGreaterThanOrEqual(0, $queryStats->getDurationInMs());

                $resultEtag = $queryStats->getResultEtag();

                $numberOfRequests = $session->advanced()->getNumberOfRequests();

                $value1 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                $this->assertEquals("Torun", $value1->getValue()->getCity());

                $this->assertEquals($numberOfRequests, $session->advanced()->getNumberOfRequests());

                $companies = $session->query(get_class($company))
                        ->statistics($queryStats)
                        ->include(function($b) {
                            $b->includeCompareExchangeValue("externalId");
                        })
                        ->toList();

                $this->assertCount(1, $companies);
                $this->assertEquals(-1, $queryStats->getDurationInMs());
                $this->assertEquals($resultEtag, $queryStats->getResultEtag());

                $innerSession = $store->openSession($sessionOptions);
                try {
                    $value = $innerSession->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                    $value->getValue()->setCity("Bydgoszcz");

                    $innerSession->saveChanges();
                } finally {
                    $innerSession->close();
                }

                $companies = $session->query(get_class($company))
                        ->statistics($queryStats)
                        ->include(function($b) {$b->includeCompareExchangeValue("externalId"); })
                        ->toList();

                $this->assertCount(1, $companies);
                $this->assertGreaterThanOrEqual(0, $queryStats->getDurationInMs()); // not from cache
                $this->assertNotEquals($resultEtag, $queryStats->getResultEtag());

                $value1 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                $this->assertEquals("Bydgoszcz", $value1->getValue()->getCity());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanUseCompareExchangeValueIncludesInQueries_Dynamic_JavaScript(): void
    {
        $store = $this->getDocumentStore();
        try {

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $employee = new Employee();
                $employee->setId("employees/1");
                $employee->setNotes(["companies/cf", "companies/hr"]);
                $session->store($employee);

                $company = new Company();
                $company->setId("companies/1");
                $company->setExternalId("companies/cf");
                $company->setName("CF");
                $session->store($company);

                $address1 = new Address();
                $address1->setCity("Torun");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/cf", $address1);

                $address2 = new Address();
                $address2->setCity("Hadera");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/hr", $address2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $statsRef = new QueryStatistics();
                $companies = $session->advanced()->rawQuery(get_class($company), "declare function incl(c) {\n" .
                        "    includes.cmpxchg(c.externalId);\n" .
                        "    return c;\n" .
                        "}\n" .
                        "from Companies as c\n" .
                        "select incl(c)")
                        ->statistics($statsRef)
                        ->toList();

                $this->assertCount(1, $companies);

                $this->assertGreaterThanOrEqual(0, $statsRef->getDurationInMs());

                $resultEtag = $statsRef->getResultEtag();

                $numberOfRequests = $session->advanced()->getNumberOfRequests();

                $value1 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                $this->assertEquals("Torun", $value1->getValue()->getCity());

                $this->assertEquals($numberOfRequests, $session->advanced()->getNumberOfRequests());

                $companies = $session->advanced()->rawQuery(get_class($company), "declare function incl(c) {\n" .
                        "    includes.cmpxchg(c.externalId);\n" .
                        "    return c;\n" .
                        "}\n" .
                        "from Companies as c\n" .
                        "select incl(c)")
                        ->statistics($statsRef)
                        ->toList();

                $this->assertCount(1, $companies);
                $this->assertEquals(-1, $statsRef->getDurationInMs()); // from cache
                $this->assertEquals($resultEtag, $statsRef->getResultEtag());

                $innerSession = $store->openSession($sessionOptions);
                try {
                    $value = $innerSession->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                    $value->getValue()->setCity("Bydgoszcz");

                    $innerSession->saveChanges();
                } finally {
                    $innerSession->close();
                }

                $companies = $session->advanced()->rawQuery(get_class($company), "declare function incl(c) {\n" .
                        "    includes.cmpxchg(c.externalId);\n" .
                        "    return c;\n" .
                        "}\n" .
                        "from Companies as c\n" .
                        "select incl(c)")
                        ->statistics($statsRef)
                        ->toList();

                $this->assertCount(1, $companies);

                $this->assertGreaterThanOrEqual(0, $statsRef->getDurationInMs()); // from cache
                $this->assertNotEquals($resultEtag, $statsRef->getResultEtag());

                $value1 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                $this->assertEquals("Bydgoszcz", $value1->getValue()->getCity());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanUseCompareExchangeValueIncludesInQueries_Static(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new Companies_ByName())->execute($store);

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $employee = new Employee();
                $employee->setId("employees/1");
                $employee->setNotes(["companies/cf", "companies/hr"]);
                $session->store($employee);

                $company = new Company();
                $company->setId("companies/1");
                $company->setExternalId("companies/cf");
                $company->setName("CF");
                $session->store($company);

                $address1 = new Address();
                $address1->setCity("Torun");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/cf", $address1);

                $address2 = new Address();
                $address2->setCity("Hadera");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/hr", $address2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession($sessionOptions);
            try {
                $statsRef = new QueryStatistics();
                $companies = $session->query(get_class($company), Companies_ByName::class)
                        ->statistics($statsRef)
                        ->include(function($b) {
                            $b->includeCompareExchangeValue("externalId");
                        })
                        ->toList();

                $this->assertCount(1, $companies);

                $this->assertGreaterThanOrEqual(0, $statsRef->getDurationInMs());

                $resultEtag = $statsRef->getResultEtag();

                $numberOfRequests = $session->advanced()->getNumberOfRequests();

                $value1 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                $this->assertEquals("Torun", $value1->getValue()->getCity());

                $this->assertEquals($numberOfRequests, $session->advanced()->getNumberOfRequests());

                $companies = $session->query(get_class($company), Companies_ByName::class)
                        ->statistics($statsRef)
                        ->include(function($b) {
                            $b->includeCompareExchangeValue("externalId");
                        })
                        ->toList();

                $this->assertCount(1, $companies);
                $this->assertEquals(-1, $statsRef->getDurationInMs()); // from cache
                $this->assertEquals($resultEtag, $statsRef->getResultEtag());

                $innerSession = $store->openSession($sessionOptions);
                try {
                    $value = $innerSession->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                    $value->getValue()->setCity("Bydgoszcz");

                    $innerSession->saveChanges();

                    $this->waitForIndexing($store);
                } finally {
                    $innerSession->close();
                }

                $companies = $session->query(get_class($company), Companies_ByName::class)
                        ->statistics($statsRef)
                        ->include(function($b) {
                            $b->includeCompareExchangeValue("externalId");
                        })
                        ->toList();

                $this->assertCount(1, $companies);
                $this->assertGreaterThanOrEqual(0, $statsRef->getDurationInMs()); // not from cache

                $this->assertNotEquals($resultEtag, $statsRef->getResultEtag());

                $value1 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                $this->assertEquals("Bydgoszcz", $value1->getValue()->getCity());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanUseCompareExchangeValueIncludesInQueries_Static_JavaScript(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new Companies_ByName())->execute($store);

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                $employee = new Employee();
                $employee->setId("employees/1");
                $employee->setNotes(["companies/cf", "companies/hr"]);
                $session->store($employee);

                $company = new Company();
                $company->setId("companies/1");
                $company->setExternalId("companies/cf");
                $company->setName("CF");
                $session->store($company);

                $address1 = new Address();
                $address1->setCity("Torun");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/cf", $address1);

                $address2 = new Address();
                $address2->setCity("Hadera");
                $session->advanced()->clusterTransaction()->createCompareExchangeValue("companies/hr", $address2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession($sessionOptions);
            try {
                $statsRef = new QueryStatistics();
                $companies = $session->advanced()->rawQuery(get_class($company), "declare function incl(c) {\n" .
                        "    includes.cmpxchg(c.externalId);\n" .
                        "    return c;\n" .
                        "}\n" .
                        "from index 'Companies/ByName' as c\n" .
                        "select incl(c)")
                        ->statistics($statsRef)
                        ->toList();

                $this->assertCount(1, $companies);

                $this->assertGreaterThanOrEqual(0, $statsRef->getDurationInMs());

                $resultEtag = $statsRef->getResultEtag();
                $numberOfRequests = $session->advanced()->getNumberOfRequests();

                $value1 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                $this->assertEquals("Torun", $value1->getValue()->getCity());

                $this->assertEquals($numberOfRequests, $session->advanced()->getNumberOfRequests());

                $companies = $session->advanced()->rawQuery(get_class($company), "declare function incl(c) {\n" .
                        "    includes.cmpxchg(c.externalId);\n" .
                        "    return c;\n" .
                        "}\n" .
                        "from index 'Companies/ByName' as c\n" .
                        "select incl(c)")
                        ->statistics($statsRef)
                        ->toList();

                $this->assertCount(1, $companies);

                $this->assertEquals(-1, $statsRef->getDurationInMs());

                $this->assertEquals($resultEtag, $statsRef->getResultEtag());

                $innerSession = $store->openSession($sessionOptions);
                try {
                    $value = $innerSession->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                    $value->getValue()->setCity("Bydgoszcz");

                    $innerSession->saveChanges();

                    $this->waitForIndexing($store);
                } finally {
                    $innerSession->close();
                }

                $companies = $session->advanced()->rawQuery(get_class($company), "declare function incl(c) {\n" .
                        "    includes.cmpxchg(c.externalId);\n" .
                        "    return c;\n" .
                        "}\n" .
                        "from index 'Companies/ByName' as c\n" .
                        "select incl(c)")
                        ->statistics($statsRef)
                        ->toList();

                $this->assertCount(1, $companies);

                $this->assertGreaterThanOrEqual(0, $statsRef->getDurationInMs());

                $this->assertNotEquals($resultEtag, $statsRef->getResultEtag());

                $value1 = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Address::class, $companies[0]->getExternalId());
                $this->assertEquals("Bydgoszcz", $value1->getValue()->getCity());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCompareExchangeValueTrackingInSessionStartsWith(): void
    {
        $store = $this->getDocumentStore();
        try {
            $allCompanies = [];

            $sessionOptions = new SessionOptions();
            $sessionOptions->setTransactionMode(TransactionMode::clusterWide());

            $session = $store->openSession($sessionOptions);
            try {
                for ($i = 0; $i < 10; $i++) {
                    $company = new Company();
                    $company->setId("companies/" . $i);
                    $company->setExternalId("companies/hr");
                    $company->setName("HR");

                    $allCompanies[] = $company->getId();
                    $session->advanced()->clusterTransaction()->createCompareExchangeValue($company->getId(), $company);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $results = $session
                    ->advanced()
                    ->clusterTransaction()
                    ->getCompareExchangeValues(Company::class, "comp");

                $this->assertCount(10, $results);
                $this->assertNotContains(null, $results);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $results = $session->advanced()->clusterTransaction()->getCompareExchangeValues(Company::class, $allCompanies);

                $this->assertCount(10, $results);
                $this->assertNotContains(null, $results);
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                foreach ($allCompanies as $companyId) {
                    $result = $session->advanced()->clusterTransaction()->getCompareExchangeValue(Company::class, $companyId);
                    $this->assertNotNull($result->getValue());
                    $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
