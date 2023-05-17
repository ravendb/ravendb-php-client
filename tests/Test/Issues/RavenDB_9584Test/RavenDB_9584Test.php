<?php

namespace tests\RavenDB\Test\Issues\RavenDB_9584Test;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Indexes\IndexFieldOptions;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;
use RavenDB\Documents\Queries\Query;
use tests\RavenDB\RemoteTestBase;

class RavenDB_9584Test extends RemoteTestBase
{
    private function setupStore(DocumentStoreInterface $store): void
    {
        $indexDefinition = new IndexDefinition();
        $indexDefinition->setName("test");
        $indexDefinition->setMaps(["from doc in docs.Users select new { doc.name, doc.company }"]);

        $nameIndexFieldOptions = new IndexFieldOptions();
        $nameIndexFieldOptions->setSuggestions(true);

        $companyIndexFieldOptions = new IndexFieldOptions();
        $companyIndexFieldOptions->setSuggestions(true);

        $fields = [];
        $fields["name"] = $nameIndexFieldOptions;
        $fields["company"] = $companyIndexFieldOptions;

        $indexDefinition->setFields($fields);

        $putIndexesOperation = new PutIndexesOperation($indexDefinition);

        $results = $store->maintenance()->send($putIndexesOperation);
        $this->assertCount(1, $results);

        $this->assertEquals($indexDefinition->getName(), $results[0]->getIndex());

        $session = $store->openSession();
        try {
            $ayende = new User();
            $ayende->setName("Ayende");
            $ayende->setCompany("Hibernating");

            $oren = new User();
            $oren->setName("Oren");
            $oren->setCompany("HR");

            $john = new User();
            $john->setName("John Steinbeck");
            $john->setCompany("Unknown");

            $session->store($ayende);
            $session->store($oren);
            $session->store($john);
            $session->saveChanges();

            $this->waitForIndexing($store);
        } finally {
            $session->close();
        }
    }

    public function testCanChainSuggestions(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->setupStore($store);

            $session = $store->openSession();
            try {
                $suggestionQueryResult = $session->query(User::class, Query::index("test"))
                        ->suggestUsing(function($x) { return $x->byField("name", "Owen"); })
                        ->andSuggestUsing(function($x) { return $x->byField("company", "Hiberanting"); })
                        ->execute();

                $this->assertCount(1, $suggestionQueryResult["name"]->getSuggestions());
                $this->assertEquals("oren", $suggestionQueryResult["name"]->getSuggestions()[0]);

                $this->assertCount(1, $suggestionQueryResult["company"]->getSuggestions());
                $this->assertEquals("hibernating", $suggestionQueryResult["company"]->getSuggestions()[0]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanUseAliasInSuggestions(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->setupStore($store);

            $session = $store->openSession();
            try {
                $suggestionQueryResult = $session->query(User::class, Query::index("test"))
                        ->suggestUsing(function($x) { $x->byField("name", "Owen")->withDisplayName("newName"); })
                        ->execute();

                $this->assertCount(1, $suggestionQueryResult["newName"]->getSuggestions());
                $this->assertEquals("oren", $suggestionQueryResult["newName"]->getSuggestions()[0]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanUseSuggestionsWithAutoIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->setupStore($store);

            $s = $store->openSession();
            try {
                $suggestionQueryResult = $s->query(User::class)
                        ->suggestUsing(function($x) { $x->byField("name", "Owen")->withDisplayName("newName"); })
                        ->execute();

                $this->assertCount(1, $suggestionQueryResult["newName"]->getSuggestions());
                $this->assertEquals("oren", $suggestionQueryResult["newName"]->getSuggestions()[0]);
            } finally {
                $s->close();
            }
        } finally {
            $store->close();
        }
    }
}
