<?php

namespace tests\RavenDB\Test\Issues;

use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15690Test extends RemoteTestBase
{
    public function testHasChanges_ShouldDetectDeletes(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");
                $session->delete($company);

                $changes = $session->advanced()->whatChanged();
                $this->assertCount(1, $changes);
                $this->assertTrue($session->advanced()->hasChanges());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
