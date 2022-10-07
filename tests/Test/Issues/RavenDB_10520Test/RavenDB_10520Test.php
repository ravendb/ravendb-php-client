<?php

namespace tests\RavenDB\Test\Issues\RavenDB_10520Test;

use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Issues\RavenDB_9745Test\Companies_ByName;

// !status: DONE
class RavenDB_10520Test extends RemoteTestBase
{
    public function testQueryCanReturnResultAsArray(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new Companies_ByName())->execute($store);

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $company1->setName("Micro");

                $company2 = new Company();
                $company2->setName("Microsoft");

                $session->store($company1);
                $session->store($company2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                /** @var array<Company> $companies */
                $companies = $session
                    ->advanced()
                    ->documentQuery(Company::class)
                    ->search("name", "Micro*")
                    ->toArray();

                $this->assertCount(2, $companies);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $companies = $session
                    ->query(Company::class)
                    ->toArray();

                $this->assertCount(2, $companies);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $companies = $session
                    ->query(Company::class)
                    ->selectFields(CompanyName::class)
                    ->toArray();

                $this->assertCount(2, $companies);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
