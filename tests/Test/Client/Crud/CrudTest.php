<?php

namespace tests\RavenDB\Test\Client\Crud;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\Crud\Entities\Family;

class CrudTest extends RemoteTestBase
{
    /**
     * @throws IllegalArgumentException
     * @throws IllegalStateException
     */
    public function testCrudOperationsWithArrayInObject()
    {
        $store = $this->getDocumentStore();

        try {
            $newSession = $store->openSession();

            try {
                $names = [
                    "Hibernating Rhinos",
                    "RavenDB",
                ];

                $family = new Family();
                $family->setNames($names);

                $newSession->store($family, 'family/1');
                $newSession->saveChanges();

                /** @var Family $newFamily */
                $newFamily = $newSession->load(Family::class, 'family/1');

                $newFamily->setNames([
                    "Toli",
                    "Mitzi",
                    "Boki",
                ]);

                $this->assertEquals(1, count($newSession->advanced()->whatChanged()));

                $newSession->saveChanges();


            } finally {
//                $newSession->close();  // @todo: check do we need this line here. I think we don't need it.
            }
        } finally {
            $this->cleanUp($store);
        }
    }
}
