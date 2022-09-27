<?php

namespace tests\RavenDB\Test\Issues\RavenDB_6558Test;

use RavenDB\Documents\Queries\Highlighting\HighlightingOptions;
use RavenDB\Documents\Queries\Highlighting\Highlightings;
use RavenDB\Documents\Queries\Query;
use tests\RavenDB\RemoteTestBase;

// !status: DONE
class RavenDB_6558Test extends RemoteTestBase
{
    public function testCanUseDifferentPreAndPostTagsPerField(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $eventsItem = new EventsItem();
                $eventsItem->setSlug("ravendb-indexes-explained");
                $eventsItem->setTitle("RavenDB indexes explained");
                $eventsItem->setContent("Itamar Syn-Hershko: Afraid of Map/Reduce? In this session, core RavenDB developer Itamar Syn-Hershko will walk through the RavenDB indexing process, grok it and much more.");
                $session->store($eventsItem, "items/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            (new ContentSearchIndex())->execute($store);

            $options1 = new HighlightingOptions();
            $options1->setPreTags(["***"]);
            $options1->setPostTags(["***"]);

            $options2 = new HighlightingOptions();
            $options2->setPreTags(["^^^"]);
            $options2->setPostTags(["^^^"]);

            $session = $store->openSession();
            try {
                $titleHighlighting = new Highlightings();
                $contentHighlighting = new Highlightings();

                $results = $session->query(SearchableInterface::class, Query::index("ContentSearchIndex"))
                    ->waitForNonStaleResults()
                    ->highlight("title", 128, 2, $options1, $titleHighlighting)
                    ->highlight("content", 128, 2, $options2, $contentHighlighting)
                    ->search("title", "RavenDB")->boost(12)
                    ->search("content", "RavenDB")
                    ->toList();

                $this->assertStringContainsString("***", $titleHighlighting->getFragments("items/1")[0]);
                $this->assertStringContainsString("^^^", $contentHighlighting->getFragments("items/1")[0]);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
