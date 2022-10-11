<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Commands\Batches\BatchPatchCommandData;
use RavenDB\Documents\Commands\Batches\IdAndChangeVector;
use RavenDB\Documents\Operations\PatchRequest;
use RavenDB\Exceptions\ConcurrencyException;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RemoteTestBase;

class RavenDB_12169Test extends RemoteTestBase
{
    public function testCanUseBatchPatchCommand(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $company1->setId("companies/1");
                $company1->setName("C1");
                $session->store($company1);

                $company2 = new Company();
                $company2->setId("companies/2");
                $company2->setName("C2");
                $session->store($company2);

                $company3 = new Company();
                $company3->setId("companies/3");
                $company3->setName("C3");
                $session->store($company3);

                $company4 = new Company();
                $company4->setId("companies/4");
                $company4->setName("C4");
                $session->store($company4);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $c1 = $session->load(Company::class, "companies/1");
                $c2 = $session->load(Company::class, "companies/2");
                $c3 = $session->load(Company::class, "companies/3");
                $c4 = $session->load(Company::class, "companies/4");

                $this->assertEquals("C1", $c1->getName());
                $this->assertEquals("C2", $c2->getName());
                $this->assertEquals("C3", $c3->getName());
                $this->assertEquals("C4", $c4->getName());

                $ids = [$c1->getId(), $c3->getId()];

                $session->advanced()->defer(new BatchPatchCommandData(
                        PatchRequest::forScript("this.name = 'test'; "),
                        null,
                        $ids
                ));

                $session->advanced()->defer(new BatchPatchCommandData(
                        PatchRequest::forScript("this.name = 'test2'; "),
                        null,
                        $c4->getId()
                ));

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $c1 = $session->load(Company::class, "companies/1");
                $c2 = $session->load(Company::class, "companies/2");
                $c3 = $session->load(Company::class, "companies/3");
                $c4 = $session->load(Company::class, "companies/4");

                $this->assertEquals("test", $c1->getName());
                $this->assertEquals("C2", $c2->getName());
                $this->assertEquals("test", $c3->getName());
                $this->assertEquals("test2", $c4->getName());

            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $c2 = $session->load(Company::class, "companies/2");

                $session->advanced()->defer(new BatchPatchCommandData(
                    PatchRequest::forScript("this.name = 'test2'"),
                        null,
                    IdAndChangeVector::create($c2->getId(), "invalidCV")
                ));

                $this->expectException(ConcurrencyException::class);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $c1 = $session->load(Company::class, "companies/1");
                $c2 = $session->load(Company::class, "companies/2");
                $c3 = $session->load(Company::class, "companies/3");
                $c4 = $session->load(Company::class, "companies/4");

                $this->assertEquals("test", $c1->getName());
                $this->assertEquals("C2", $c2->getName());
                $this->assertEquals("test", $c3->getName());
                $this->assertEquals("test2", $c4->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
