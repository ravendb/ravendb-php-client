<?php

namespace tests\RavenDB\Test\Querying\_HighlightesTest;

use RavenDB\Documents\Queries\Highlighting\HighlightingOptions;
use RavenDB\Documents\Queries\Highlighting\Highlightings;
use RavenDB\Documents\Queries\Query;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;

class HighlightesTest extends RemoteTestBase
{
    public function testSearchWithHighlights(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $q = "session";

        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $eventsItem = new EventsItem();
                $eventsItem->setSlug("ravendb-indexes-explained");
                $eventsItem->setTitle("RavenDB indexes explained");
                $eventsItem->setContent("Itamar Syn-Hershko: Afraid of Map/Reduce? In this session, core RavenDB developer Itamar Syn-Hershko will walk through the RavenDB indexing process, grok it and much more.");
                $session->store($eventsItem);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            (new ContentSearchIndex())->execute($store);

            $session = $store->openSession();
            try {
                $options = new HighlightingOptions();
                $options->setPreTags([ "<span style='background: yellow'>" ]);
                $options->setPostTags([ "</span>" ]);

                $titleHighlighting = new Highlightings();
                $slugHighlighting = new Highlightings();
                $contentHighlighting = new Highlightings();

                $results = $session->query(null, Query::index("ContentSearchIndex"))
                        ->waitForNonStaleResults()
                        ->highlight("title", 128, 2, $options, $titleHighlighting)
                        ->highlight("slug", 128, 2, $options, $slugHighlighting)
                        ->highlight("content", 128, 2, $options, $contentHighlighting)
                        ->search("slug", $q)->boost(15)
                        ->search("title", $q)->boost(12)
                        ->search("content", $q)
                        ->toList();

                $orderedResults = [];
                /** @var SearchableInterface $searchable */
                foreach ($results as $searchable) {
                    $docId = $session->advanced()->getDocumentId($searchable);

                    $highlights = [];

                    $title = null;
                    $titles = $titleHighlighting->getFragments($docId);
                    if (count($titles) == 1) {
                        $title = $titles[0];
                    } else {
                        $highlights = array_merge($highlights, $titleHighlighting->getFragments($docId));
                    }

                        $highlights = array_merge($highlights, $slugHighlighting->getFragments($docId));
                        $highlights = array_merge($highlights, $contentHighlighting->getFragments($docId));

                    $searchResults = new SearchResults();
                    $searchResults->setResult($searchable);
                    $searchResults->setHighlights($highlights);
                    $searchResults->setTitle($title);
                    $orderedResults[] = $searchResults;
                }

                $this->assertCount(1, $orderedResults);

                $this->assertCount(1, $orderedResults[0]->getHighlights());

                $firstHighlight = $orderedResults[0]->getHighlights()[0];
                $this->assertStringContainsString(">session<", $firstHighlight);

                $this->assertEquals("RavenDB indexes explained", $orderedResults[0]->getResult()->title);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
