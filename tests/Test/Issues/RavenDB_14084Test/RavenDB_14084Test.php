<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14084Test;

use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Operations\Indexes\GetIndexesOperation;
use RavenDB\Documents\Session\SessionOptions;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RemoteTestBase;

class RavenDB_14084Test extends RemoteTestBase
{
    public function testCanIndexMissingFieldsAsNull_Static(): void
    {
        $store = $this->getDocumentStore();

        try {
            (new Companies_ByUnknown())->execute($store);
            (new Companies_ByUnknown_WithIndexMissingFieldsAsNull())->execute($store);

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $sessionOptions = new SessionOptions();
            $sessionOptions->setNoCaching(true);

            $session = $store->openSession($sessionOptions);
            try {
                $companies = $session
                        ->advanced()
                        ->documentQuery(Company::class, Companies_ByUnknown::class)
                        ->whereEquals("Unknown", null)
                        ->toList();

                $this->assertCount(0, $companies);
            } finally {
                $session->close();
            }

            $session = $store->openSession($sessionOptions);
            try {
                $companies = $session
                    ->advanced()
                    ->documentQuery(Company::class, Companies_ByUnknown_WithIndexMissingFieldsAsNull::class)
                    ->whereEquals("Unknown", null)
                    ->toList();

                $this->assertCount(1, $companies);
            } finally {
                $session->close();
            }

            /** @var IndexDefinition[] $indexDefinitions */
            $indexDefinitions = $store->maintenance()->send(new GetIndexesOperation(0, 10));
            $this->assertCount(2, $indexDefinitions);

            $configuration = null;
            foreach ($indexDefinitions as $indexDefinition) {
                if ($indexDefinition->getName() == "Companies/ByUnknown/WithIndexMissingFieldsAsNull") {
                    $configuration = $indexDefinition->getConfiguration();
                    break;
                }
            }

            $this->assertCount(1, $configuration);
            $this->assertArrayHasKey("Indexing.IndexMissingFieldsAsNull", $configuration);
            $this->assertEquals("true", $configuration["Indexing.IndexMissingFieldsAsNull"]);
        } finally {
            $store->close();
        }
    }
}
