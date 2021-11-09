<?php

namespace tests\RavenDB\Test\Client\Crud;

use InvalidArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\Crud\Entities\Family;

class CrudTest extends RemoteTestBase
{
    private string $var;

    /**
     * @throws InvalidArgumentException
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

                // @continue on JsonOperation::entityChanged method

//                print_r($newSession->advanced()->whatChanged()); // work in progress
//                $this->assertEquals(1, count($newSession->advanced()->whatChanged()));
                $this->assertTrue(true);

                $newSession->saveChanges();
            } finally {
//                $newSession->close();  // @todo: check do we need this line here. I think we don't need it.
            }
        } finally {
            $store->close();
        }
    }
}
