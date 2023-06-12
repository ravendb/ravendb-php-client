<?php

namespace tests\RavenDB\Test\Client\_CustomEntityNameTest;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Queries\Query;
use tests\RavenDB\RemoteTestBase;

class CustomEntityNameTest extends RemoteTestBase
{
    private $c = '';

    private static function getChars(): array
    {
        $basicChars = [];
        for ($i = 1; $i < 32; $i++) {
            $basicChars[] = chr($i);
        }

        $extraChars = [ 'a', '-', '\'', '\"', '\\', '\b', '\f', '\n', '\r', '\t' ];
        return array_merge($basicChars, $extraChars);
    }

    private static function getCharactersToTestWithSpecial(): array
    {
        $basicChars = self::getChars();
        $specialChars = [ 'Ā', 'Ȁ', 'Ѐ', 'Ԁ', '؀', '܀', 'ऀ', 'ਅ', 'ଈ', 'అ', 'ഊ', 'ข', 'ဉ', 'ᄍ', 'ሎ', 'ጇ', 'ᐌ', 'ᔎ', 'ᘀ', 'ᜩ', 'ᢹ', 'ᥤ', 'ᨇ' ];
        return array_merge($basicChars, $specialChars);
    }

    public function testFindCollectionName(): void
    {
        foreach (self::getCharactersToTestWithSpecial() as $c) {
            $this->_testWhenCollectionAndIdContainSpecialChars($c);
        }
    }

    protected function customizeStore(DocumentStore &$store): void
    {
        $store->getConventions()->setFindCollectionName(function($className) {
            return "Test" . $this->c . DocumentConventions::defaultGetCollectionName($className);
        });
    }

    private function _testWhenCollectionAndIdContainSpecialChars(string $c): void {
        if (mb_ord($c) >= 14 && mb_ord($c) <= 31) {
            return;
        }

        $this->c = $c;

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $car = new Car();
                $car->setManufacturer("BMW");
                $session->store($car);
                $user = new User();
                $user->setCarId($car->getId());
                $session->store($user);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $findCollectionName = $store->getConventions()->getFindCollectionName();
                $results = $session->query(User::class, Query::collection($findCollectionName(User::class)))
                        ->toList();
                $this->assertCount(1, $results);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
