<?php

namespace tests\RavenDB\Test\Issues;

use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15080Test extends RemoteTestBase
{
    /** @doesNotPerformAssertions */
    public function testCanSplitLowerCasedAndUpperCasedCounterNames(): void
    {
        $storeA = $this->getDocumentStore();
        try {
            $session = $storeA->openSession();
            try {
                $user = new User();
                $user->setName("Aviv1");
                $session->store($user, "users/1");

                $countersFor = $session->countersFor("users/1");

                for ($i = 0; $i < 500; $i++) {
                    $str = "abc" . $i;
                    $countersFor->increment($str);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $storeA->openSession();
            try {
                $countersFor = $session->countersFor("users/1");

                for ($i = 0; $i < 500; $i++) {
                    $str = "Xyz" . $i;
                    $countersFor->increment($str);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $storeA->close();
        }
    }

    public function testCounterOperationsShouldBeCaseInsensitiveToCounterName(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Aviv");
                $session->store($user, "users/1");

                $session->countersFor("users/1")->increment("abc");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                // should NOT create a new counter
                $session->countersFor("users/1")->increment("ABc");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $this->assertCount(1, $session->countersFor("users/1")->getAll());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                // get should be case-insensitive to counter name

                $val = $session->countersFor("users/1")->get("AbC");
                $this->assertEquals(2, $val);

                $doc = $session->load(User::class, "users/1");
                $countersNames = $session->advanced()->getCountersFor($doc);
                $this->assertCount(1, $countersNames);
                $this->assertEquals("abc", $countersNames[0]);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->countersFor("users/1")->increment("XyZ");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->countersFor("users/1")
                    ->get("xyz");
                $this->assertEquals(1, $val);

                $doc = $session->load(User::class, "users/1");
                $counterNames = $session->advanced()->getCountersFor($doc);
                $this->assertCount(2, $counterNames);

                // metadata counter-names should preserve their original casing
                $this->assertEquals("abc", $counterNames[0]);
                $this->assertEquals("XyZ", $counterNames[1]);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                // delete should be case-insensitive to counter name

                $session->countersFor("users/1")->delete("aBC");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->countersFor("users/1")->get("abc");
                $this->assertNull($val);

                $doc = $session->load(User::class, "users/1");
                $counterNames = $session->advanced()->getCountersFor($doc);
                $this->assertCount(1, $counterNames);
                $this->assertContains("XyZ", $counterNames);

            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->countersFor("users/1")->delete("xyZ");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $val = $session->countersFor("users/1")->get("Xyz");
                $this->assertNull($val);

                $doc = $session->load(User::class, "users/1");
                $counterNames = $session->advanced()->getCountersFor($doc);
                $this->assertNull($counterNames);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCountersShouldBeCaseInsensitive(): void
    {
        // RavenDB-14753

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");

                $session->store($company, "companies/1");
                $session->countersFor($company)->increment("Likes", 999);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");
                $session->countersFor($company)
                    ->delete("lIkEs");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");
                $counters = $session->countersFor($company)->getAll();
                $this->assertEmpty($counters);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testDeletedCounterShouldNotBePresentInMetadataCounters(): void
    {
        // RavenDB-14753

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");

                $session->store($company, "companies/1");
                $session->countersFor($company)->increment("Likes", 999);
                $session->countersFor($company)->increment("Cats", 999);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");
                $session->countersFor($company)
                    ->delete("lIkEs");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");
                $company->setName("RavenDB");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");
                $counters = $session->advanced()->getCountersFor($company);
                $this->assertCount(1, $counters);
                $this->assertContains("Cats", $counters);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testGetCountersForDocumentShouldReturnNamesInTheirOriginalCasing(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $session->store(new User(), "users/1");

                $countersFor = $session->countersFor("users/1");
                $countersFor->increment("AviV");
                $countersFor->increment("Karmel");
                $countersFor->increment("PAWEL");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                // GetAll should return counter names in their original casing
                $all = $session->countersFor("users/1")
                    ->getAll();

                $this->assertCount(3, $all);

                $keys = array_keys($all);
                $this->assertContains("AviV", $keys);
                $this->assertContains("Karmel", $keys);
                $this->assertContains("PAWEL", $keys);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanDeleteAndReInsertCounter(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");

                $session->store($company, "companies/1");
                $session->countersFor($company)->increment("Likes", 999);
                $session->countersFor($company)->increment("Cats", 999);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");
                $session->countersFor($company)
                        ->delete("Likes");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");
                $counters = $session->advanced()->getCountersFor($company);

                $this->assertCount(1, $counters);
                $this->assertContains("Cats", $counters);

                $counter = $session->countersFor($company)
                        ->get("Likes");
                $this->assertNull($counter);

                $all = $session->countersFor($company)
                        ->getAll();

                $this->assertCount(1, $all);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->countersFor("companies/1")->increment("Likes");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");
                $counters = $session->advanced()->getCountersFor($company);

                $this->assertCount(2, $counters);
                $this->assertContains("Cats", $counters);
                $this->assertContains("Likes", $counters);

                $counter = $session->countersFor($company)
                        ->get("Likes");
                $this->assertNotNull($counter);

                $this->assertEquals(1, $counter);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCountersSessionCacheShouldBeCaseInsensitiveToCounterName(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");

                $session->store($company, "companies/1");
                $session->countersFor($company)
                        ->increment("Likes", 333);
                $session->countersFor($company)
                        ->increment("Cats", 999);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");

                // the document is now tracked by the session,
                // so now counters-cache has access to '@counters' from metadata

                // searching for the counter's name in '@counters' should be done in a case insensitive manner
                // counter name should be found in '@counters' => go to server

                $counter = $session->countersFor($company)
                        ->get("liKes");
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertNotNull($counter);
                $this->assertEquals(333, $counter);

                $counter = $session->countersFor($company)->get("cats");
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
                $this->assertNotNull($counter);
                $this->assertEquals(999, $counter);

                $counter = $session->countersFor($company)->get("caTS");
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
                $this->assertNotNull($counter);
                $this->assertEquals(999, $counter);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
