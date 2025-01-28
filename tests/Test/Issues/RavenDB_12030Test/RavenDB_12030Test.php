<?php

namespace tests\RavenDB\Test\Issues\RavenDB_12030Test;

use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Infrastructure\Entity\Company;

class RavenDB_12030Test extends RemoteTestBase
{
    public function testSimpleFuzzy(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $hr = new Company();
                $hr->setName("Hibernating Rhinos");
                $session->store($hr);

                $cf = new Company();
                $cf->setName("CodeForge");
                $session->store($cf);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $companies = $session
                    ->advanced()
                    ->documentQuery(Company::class)
                    ->whereEquals("name", "CoedForhe")
                    ->fuzzy(0.5)
                    ->toList();

                $this->assertCount(1, $companies);
                $this->assertEquals("CodeForge", $companies[0]->getName());

                $companies = $session
                    ->advanced()
                    ->documentQuery(Company::class)
                    ->whereEquals("name", "Hiberanting Rinhos")
                    ->fuzzy(0.5)
                    ->toList();

                $this->assertCount(1, $companies);
                $this->assertEquals("Hibernating Rhinos", $companies[0]->getName());

                $companies = $session
                    ->advanced()
                    ->documentQuery(Company::class)
                    ->whereEquals("name", "CoedForhe")
                    ->fuzzy(0.99)
                    ->toList();

                $this->assertCount(0, $companies);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testSimpleProximity(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            (new Fox_Search())->execute($store);

            $session = $store->openSession();
            try {
                $f1 = new Fox();
                $f1->setName("a quick brown fox");
                $session->store($f1);

                $f2 = new Fox();
                $f2->setName("the fox is quick");
                $session->store($f2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $foxes = $session
                        ->advanced()
                        ->documentQuery(Fox::class, Fox_Search::class)
                        ->search("name", "quick fox")
                        ->proximity(1)
                        ->toList();

                $this->assertCount(1, $foxes);
                $this->assertEquals("a quick brown fox", $foxes[0]->getName());

                $foxes = $session
                        ->advanced()
                        ->documentQuery(Fox::class, Fox_Search::class)
                        ->search("name", "quick fox")
                        ->proximity(2)
                        ->toList();

                $this->assertCount(2, $foxes);
                $this->assertEquals("a quick brown fox", $foxes[0]->getName());
                $this->assertEquals("the fox is quick", $foxes[1]->getName());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
