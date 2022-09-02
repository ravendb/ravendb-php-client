<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15825Test;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Queries\Facets\FacetOptions;
use RavenDB\Documents\Queries\Facets\FacetResultArray;
use RavenDB\Documents\Queries\HashCalculator;
use RavenDB\Documents\Session\DocumentSessionInterface;
use RavenDB\Documents\Session\OrderingType;
use RavenDB\Documents\Session\QueryStatistics;
use RavenDB\Parameters;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15825Test extends RemoteTestBase
{
    private static array $TAGS = ["test", "label", "vip", "apple", "orange"];

    public function testShouldWork(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new ContactsIndex())->execute($store);

            $session = $store->openSession();
            try {
                for ($id = 0; $id < 10000; $id++) {
                    $companyId = $id % 100;

                    $contact = new Contact();
                    $contact->setId("contacts/" . $id);
                    $contact->setCompanyId($companyId);
                    $contact->setActive($id % 2 == 0);
                    $contact->setTags([self::$TAGS[$id % count(self::$TAGS)]]);

                    $session->store($contact);
                }

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {

                $statsRef = new QueryStatistics();
                $res = self::facet($session, 1, 3, $statsRef);

                $this->assertGreaterThanOrEqual(0, $statsRef->getDurationInMs());
                $this->assertCount(3, $res["companyId"]->getValues());

                $this->assertEquals("28", $res->offsetGet("companyId")->getValues()[0]->getRange());
                $this->assertEquals("38", $res->offsetGet("companyId")->getValues()[1]->getRange());
                $this->assertEquals("48", $res->offsetGet("companyId")->getValues()[2]->getRange());

                $stats2Ref = new QueryStatistics();
                $res2 = self::facet($session, 2, 1, $stats2Ref);
                $this->assertGreaterThanOrEqual(0, $stats2Ref->getDurationInMs());
                $this->assertCount(1, $res2["companyId"]->getValues());

                $this->assertEquals("38", $res2->offsetGet("companyId")->getValues()[0]->getRange());

                $stats3Ref = new QueryStatistics();
                $res3 = self::facet($session, 5, 5, $stats3Ref);
                $this->assertGreaterThanOrEqual(0, $stats3Ref->getDurationInMs());
                $this->assertCount(5, $res3["companyId"]->getValues());

                $this->assertEquals("68", $res3->offsetGet("companyId")->getValues()[0]->getRange());
                $this->assertEquals("78", $res3->offsetGet("companyId")->getValues()[1]->getRange());
                $this->assertEquals("8", $res3->offsetGet("companyId")->getValues()[2]->getRange());
                $this->assertEquals("88", $res3->offsetGet("companyId")->getValues()[3]->getRange());
                $this->assertEquals("98", $res3->offsetGet("companyId")->getValues()[4]->getRange());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanHashCorrectly(): void
    {
        $facetOptions = new FacetOptions();
        $facetOptions->setStart(1);
        $facetOptions->setPageSize(5);

        $p = new Parameters();
        $p->offsetSet("p1", $facetOptions);

        $hashCalculator = new HashCalculator();
        $hashCalculator->write($p, DocumentConventions::getDefaultConventions()->getEntityMapper());
        $hash1 = $hashCalculator->getHash();

        // create second object with same props
        $facetOptions2 = new FacetOptions();
        $facetOptions2->setStart(1);
        $facetOptions2->setPageSize(5);

        $p2 = new Parameters();
        $p2->offsetSet("p1", $facetOptions2);

        $hashCalculator2 = new HashCalculator();
        $hashCalculator2->write($p2, DocumentConventions::getDefaultConventions()->getEntityMapper());
        $hash2 = $hashCalculator2->getHash();

        // modify original object - it should change hash
        $facetOptions->setStart(2);
        $hashCalculator3 = new HashCalculator();
        $hashCalculator3->write($p, DocumentConventions::getDefaultConventions()->getEntityMapper());
        $hash3 = $hashCalculator3->getHash();

        $this->assertEquals($hash1, $hash2); // structural equality

        $this->assertNotEquals($hash1, $hash3); // same reference - different structure
    }

    private static function facet(?DocumentSessionInterface $session, int $skip, int $take, QueryStatistics &$statsRef): FacetResultArray
    {
        $facetOptions = new FacetOptions();
        $facetOptions->setStart($skip);
        $facetOptions->setPageSize($take);

        return $session->query(Result::class, ContactsIndex::class)
            ->statistics($statsRef)
            ->orderBy("companyId", OrderingType::alphaNumeric())
            ->whereEquals("active", true)
            ->whereEquals("tags", "apple")
            ->aggregateBy(function($b) use ($facetOptions) { $b->byField("companyId")->withOptions($facetOptions); })
            ->execute()
        ;
    }
}
