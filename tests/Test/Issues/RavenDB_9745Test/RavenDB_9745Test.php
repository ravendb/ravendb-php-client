<?php

namespace tests\RavenDB\Test\Issues\RavenDB_9745Test;

use RavenDB\Documents\Queries\Explanation\ExplanationOptions;
use RavenDB\Documents\Queries\Explanation\Explanations;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;

class RavenDB_9745Test extends RemoteTestBase
{
    public function testExplain(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {

            (new Companies_ByName())->execute($store);

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $company1->setName("Micro");

                $company2 = new Company();
                $company2->setName("Microsoft");

                $company3 = new Company();
                $company3->setName("Google");

                $session->store($company1);
                $session->store($company2);
                $session->store($company3);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $explanationsReference = new Explanations();
                /** @var array<Company> $companies */
                $companies = $session
                        ->advanced()
                        ->documentQuery(Company::class)
                        ->includeExplanations(null, $explanationsReference)
                        ->search("name", "Micro*")
                        ->toList();

                $this->assertCount(2, $companies);

                $exp = $explanationsReference->getExplanations($companies[0]->getId());
                $this->assertNotNull($exp);

                $exp = $explanationsReference->getExplanations($companies[1]->getId());
                $this->assertNotNull($exp);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $options = new ExplanationOptions();
                $options->setGroupKey("key");

                $explanationsReference = new Explanations();

                $results = $session
                    ->advanced()
                    ->documentQuery(Companies_ByNameResult::class, Companies_ByName::class)
                    ->includeExplanations($options, $explanationsReference)
                    ->toList();


                $this->assertCount(3, $results);

                $exp = $explanationsReference->getExplanations($results[0]->getKey());
                $this->assertNotNull($exp);

                $exp = $explanationsReference->getExplanations($results[1]->getKey());
                $this->assertNotNull($exp);

                $exp = $explanationsReference->getExplanations($results[2]->getKey());
                $this->assertNotNull($exp);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
