<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Queries\Timings\QueryTimings;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RemoteTestBase;

class RavenDB_9587Test extends RemoteTestBase
{
    public function testTimingsShouldWork(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $c1 = new Company();
                $c1->setName("CF");

                $c2 = new Company();
                $c2->setName("HR");

                $session->store($c1);
                $session->store($c2);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $timingsReference = new QueryTimings();

                /** @var array<Company> $companies */
                $companies = $session
                        ->query(Company::class)
                        ->timings($timingsReference)
                        ->whereNotEquals("name", "HR")
                        ->toList();

                $this->assertGreaterThan(0, $timingsReference->getDurationInMs());
                $this->assertNotNull($timingsReference->getTimings());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
