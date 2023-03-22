<?php

namespace tests\RavenDB\Test\Issues;

use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15402Test extends RemoteTestBase
{
    public function testGetCountersShouldBeCaseInsensitive(): void
    {
        $store = $this->getDocumentStore();
        try {
            $id = "companies/1";

            $session = $store->openSession();
            try {
                $session->store(new Company(), $id);
                $session->countersFor($id)
                        ->increment("Likes", 999);
                $session->countersFor($id)
                        ->increment("DisLikes", 999);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, $id);
                $counters = $session->countersFor($company)
                        ->get(["likes", "dislikes"]);

                $this->assertCount(2, $counters);
                $this->assertEquals(999, $counters["likes"]);
                $this->assertEquals(999, $counters["dislikes"]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
