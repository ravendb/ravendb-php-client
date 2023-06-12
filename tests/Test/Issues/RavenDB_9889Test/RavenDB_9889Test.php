<?php

namespace tests\RavenDB\Test\Issues\RavenDB_9889Test;

use tests\RavenDB\RemoteTestBase;

class RavenDB_9889Test extends RemoteTestBase
{
    public function testCanUseToDocumentConversionEvents(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->addBeforeConversionToDocumentListener(function ($sender, $event) {
                if ($event->getEntity() instanceof Item) {
                    /** @var Item $item */
                    $item = $event->getEntity();
                    $item->setBefore(true);
                }
            });

            $store->addAfterConversionToDocumentListener(function($sender, $event) {
                if ($event->getEntity() instanceof Item) {
                    /** @var Item $item */
                    $item = $event->getEntity();
                    $document = $event->getDocument();
                    $document["after"] = true;
                    $event->setDocument($document);

                    $item->setAfter(true);
                }
            });

            $session = $store->openSession();
            try {
                $session->store(new Item(), "items/1");
                $session->saveChanges();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $session->saveChanges();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var Item $item */
                $item = $session->load(Item::class, "items/1");

                $this->assertNotNull($item);

                $this->assertTrue($item->isBefore());
                $this->assertTrue($item->isAfter());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanUseToEntityConversionEvents(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->addBeforeConversionToEntityListener(function ($sender, $event) {
                $event->getDocument()["before"] = true;
            });

            $store->addAfterConversionToEntityListener(function($sender, $event) {
                if ($event->getEntity() instanceof Item) {
                    /** @var Item $item */
                    $item = $event->getEntity();
                    $item->setAfter(true);
                }

                if ($event->getEntity() instanceof ProjectedItem) {
                    /** @var ProjectedItem $projectedItem */
                    $projectedItem = $event->getEntity();
                    $projectedItem->setAfter(true);
                }
            });

            $session = $store->openSession();
            try {
                $session->store(new Item(), "items/1");
                $session->store(new Item(), "items/2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            // load
            $session = $store->openSession();
            try {
                /** @var Item $item */
                $item = $session->load(Item::class, "items/1");

                $this->assertNotNull($item);
                $this->assertTrue($item->isBefore());
                $this->assertTrue($item->isAfter());
            } finally {
                $session->close();
            }

            // queries
            $session = $store->openSession();
            try {
                /** @var array<Item> $items */
                $items = $session->query(Item::class)->toList();

                $this->assertCount(2, $items);

                /** @var Item $item */
                foreach ($items as $item) {
                    $this->assertTrue($item->isBefore());
                    $this->assertTrue($item->isAfter());
                }
            } finally {
                $session->close();
            }

            // projections in queries
            $session = $store->openSession();
            try {
                /** @var array<ProjectedItem> $items */
                $items = $session->query(Item::class)
                        ->selectFields(ProjectedItem::class)
                        ->toList();

                $this->assertCount(2, $items);

                /** @var ProjectedItem $item */
                foreach ($items as $item) {
                    $this->assertTrue($item->isBefore());
                    $this->assertTrue($item->isAfter());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
