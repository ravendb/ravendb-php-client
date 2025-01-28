<?php

namespace tests\RavenDB\Test\Suggestions\_SuggestionsTest;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Indexes\IndexFieldOptions;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;
use RavenDB\Documents\Queries\Query;
use RavenDB\Documents\Queries\Suggestions\StringDistanceTypes;
use RavenDB\Documents\Queries\Suggestions\SuggestionOptions;
use RavenDB\Documents\Queries\Suggestions\SuggestionSortMode;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\Indexing\_IndexesFromClientTest\Users_ByName;

class SuggestionsTest extends RemoteTestBase
{
    public function setupStore(?DocumentStoreInterface $store): void
    {
        $indexDefinition = new IndexDefinition();
        $indexDefinition->setName("test");
        $indexDefinition->setMaps([ "from doc in docs.Users select new { doc.name }" ]);

        $indexFieldOptions = new IndexFieldOptions();
        $indexFieldOptions->setSuggestions(true);

        $indexDefinition->setFields([ "name" => $indexFieldOptions ]);

        $store->maintenance()->send(new PutIndexesOperation($indexDefinition));

        $session = $store->openSession();
        try {
            $user1 = new User();
            $user1->setName("Ayende");

            $user2 = new User();
            $user2->setName("Oren");

            $user3 = new User();
            $user3->setName("John Steinbeck");

            $session->store($user1);
            $session->store($user2);
            $session->store($user3);
            $session->saveChanges();
        } finally {
            $session->close();
        }

        $this->waitForIndexing($store);
    }

    public function testExactMatch(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            $this->setupStore($store);

            $session = $store->openSession();
            try {
                $options = new SuggestionOptions();
                $options->setPageSize(10);

                $suggestionQueryResult = $session->query(User::class, Query::index("test"))
                        ->suggestUsing(function($x) use ($options) { return $x->byField("name", "Oren")->withOptions($options); })
                        ->execute();

                $this->assertCount(0, $suggestionQueryResult["name"]->getSuggestions());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testUsingLinq(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->setupStore($store);

            $session = $store->openSession();
            try {
                $suggestionQueryResult = $session->query(User::class, Query::index("test"))
                        ->suggestUsing(function($x) { return $x->byField("name", "Owen"); })
                        ->execute();

                $this->assertCount(1, $suggestionQueryResult["name"]->getSuggestions());

                $this->assertEquals("oren", $suggestionQueryResult["name"]->getSuggestions()[0]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testUsingLinq_WithOptions(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->setupStore($store);

            $session = $store->openSession();
            try {

                $options = new SuggestionOptions();
                $options->setAccuracy(0.4);

                $suggestionQueryResult = $session->query(User::class, Query::index("test"))
                        ->suggestUsing(function($x) use ($options) { return $x->byField("name", "Orin")->withOptions($options); })
                        ->execute();

                $this->assertCount(1, $suggestionQueryResult["name"]->getSuggestions());

                $this->assertEquals("oren", $suggestionQueryResult["name"]->getSuggestions()[0]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_usingLinq_Multiple_words(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->setupStore($store);

            $s = $store->openSession();
            try {
                $options = new SuggestionOptions();
                $options->setAccuracy(0.4);
                $options->setDistance(StringDistanceTypes::levenshtein());

                $suggestionQueryResult = $s->query(User::class, Query::index("test"))
                        ->suggestUsing(function($x) use($options) { return $x->byField("name", "John Steinback")->withOptions($options); })
                        ->execute();

                $this->assertCount(1, $suggestionQueryResult["name"]->getSuggestions());

                $this->assertEquals("john steinbeck", $suggestionQueryResult["name"]->getSuggestions()[0]);
            } finally {
                $s->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testWithTypo(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            $this->setupStore($store);

            $s = $store->openSession();
            try {

                $options = new SuggestionOptions();
                $options->setAccuracy(0.2);
                $options->setPageSize(10);
                $options->setDistance(StringDistanceTypes::levenshtein());

                $suggestionQueryResult = $s->query(User::class, Query::index("test"))
                        ->suggestUsing(function($x) use ($options) { return $x->byField("name", "Oern")->withOptions($options); })
                        ->execute();

                $this->assertCount(1, $suggestionQueryResult["name"]->getSuggestions());

                $this->assertEquals("oren", $suggestionQueryResult["name"]->getSuggestions()[0]);
            } finally {
                $s->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetSuggestions(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            (new Users_ByName())->execute($store);

            $s = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("John Smith");
                $s->store($user1, "users/1");

                $user2 = new User();
                $user2->setName("Jack Johnson");
                $s->store($user2, "users/2");

                $user3 = new User();
                $user3->setName("Robery Jones");
                $s->store($user3, "users/3");

                $user4 = new User();
                $user4->setName("David Jones");
                $s->store($user4, "users/4");

                $s->saveChanges();
            } finally {
                $s->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $options = new SuggestionOptions();
                $options->setAccuracy(0.4);
                $options->setPageSize(5);
                $options->setDistance(StringDistanceTypes::jaroWinkler());
                $options->setSortMode(SuggestionSortMode::popularity());

                $suggestions = $session->query(User::class, Users_ByName::class)
                        ->suggestUsing(function($x) use ($options) { return $x->byField("name", [ "johne", "davi" ])->withOptions($options); })
                        ->execute();

                $this->assertCount(5, $suggestions["name"]->getSuggestions());

                $this->assertEquals(["john", "jones", "johnson", "david", "jack"] , $suggestions["name"]->getSuggestions()->getArrayCopy());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
