<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Session\BeforeStoreEventArgs;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RavenDB_14276Test extends RemoteTestBase
{
    /** @var array<array<int>>  */
    private  array $dictionary = [];

    public function __construct() {
        parent::__construct();

        $this->dictionary = [];
        $firstMap = [];
        $firstMap["aaaa"] = 1;

        $secondMap = [];
        $secondMap["bbbb"] = 2;

        $this->dictionary["123"] = $firstMap;
        $this->dictionary["321"] = $secondMap;
    }

    public function test_can_Update_Metadata_With_Nested_Dictionary(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->addBeforeStoreListener(function($sender, $event) {
                self::onBeforeStore($event);
            });

            $docId = "users/1";

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Some document");

                $session->store($user, $docId);

                $metadata = $session->advanced()->getMetadataFor($user);
                $metadata->put("Custom-Metadata", $this->dictionary);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, $docId);
                $user->setName("Updated document");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->verifyData($store, $docId);
        } finally {
            $store->close();
        }
    }

    public function test_can_Update_Metadata_With_Nested_Dictionary_Same_Session(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->addBeforeStoreListener(function($sender, $event) {
                self::onBeforeStore($event);
            });

            $docId = "users/1";

            $session = $store->openSession();
            try {

                $savedUser = new User();
                $savedUser->setName("Some document");
                $session->store($savedUser, $docId);

                $metadata = $session->advanced()->getMetadataFor($savedUser);
                $metadata->put("Custom-Metadata", $this->dictionary);

                $session->saveChanges();

                $user = $session->load(User::class, $docId);
                $user->setName("Updated document");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->verifyData($store, $docId);
        } finally {
            $store->close();
        }
    }

    private static function onBeforeStore(BeforeStoreEventArgs $eventArgs): void
    {
        if ($eventArgs->getDocumentMetadata()->containsKey("Some-MetadataEntry")) {
            $metadata = $eventArgs->getSession()->getMetadataFor($eventArgs->getEntity());
            $metadata->put("Some-MetadataEntry", "Updated");
        } else {
            $eventArgs->getDocumentMetadata()->put("Some-MetadataEntry", "Created");
        }
    }

    private static function verifyData(DocumentStoreInterface $store, ?string $docId): void
    {
        $session = $store->openSession();
        try {
            $user = $session->load(User::class, $docId);
            self::assertEquals("Updated document", $user->getName());

            $metadata = $session->advanced()->getMetadataFor($user);
            $dictionary = $metadata->get("Custom-Metadata");
            $nestedDictionary = $dictionary["123"];

            self::assertEquals(1, $nestedDictionary["aaaa"]);

            $nestedDictionary = $dictionary["321"];
            self::assertEquals(2, $nestedDictionary["bbbb"]);
        } finally {
            $session->close();
        }
    }

}
