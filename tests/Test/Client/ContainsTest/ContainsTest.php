<?php

namespace tests\RavenDB\Test\Client\ContainsTest;

use RavenDB\Type\Collection;
use RavenDB\Type\StringClass;
use RavenDB\Type\StringList;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\ContainsTest\Entity\UserWithFavs;

class ContainsTest extends RemoteTestBase
{
    public function atestContainsTest(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $userCreator = function ($name, $favs) use ($session) {
                    $user = new UserWithFavs();
                    $user->setName($name);
                    $user->setFavourites($favs);

                    $session->store($user);
                };

                $userCreator("John", StringList::fromArray(["java", "c#"]));
                $userCreator("Tarzan", StringList::fromArray(["java", "go"]));
                $userCreator("Jane", StringList::fromArray(["pascal"]));

                $session->saveChanges();
            } finally {
                $session->close();
            }

            try {
                $pascalOrGoDeveloperNames = $session
                    ->query(UserWithFavs::class)
                    ->containsAny("favourites", Collection::fromArray(["pascal", "go"]))
                    ->selectFields("name")
                    ->selectFields(["name"], StringClass::class,)
                    ->selectFields(StringClass::class, ["name"])
                    ->toList();

                $this->assertCount(2, $pascalOrGoDeveloperNames);
                $this->assertContains("Jane", $pascalOrGoDeveloperNames);
                $this->assertContains("Tarzan", $pascalOrGoDeveloperNames);
            } finally {
                $session->close();
            }

            try {
                $javaDevelopers = $session
                        ->query(UserWithFavs::class)
                        ->containsAll("favourites", Collection::fromArray(["java"]))
                        ->selectFields(StringClass::class, "name")
                        ->toList();

                $this->assertCount(2, $javaDevelopers);
                $this->assertContains("John", $javaDevelopers);
                $this->assertContains("Tarzan", $javaDevelopers);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
