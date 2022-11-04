<?php

namespace tests\RavenDB\Test\Server\Pathcing\_AdvancedPatchingTest;

use RavenDB\Documents\Operations\PatchByQueryOperation;
use RavenDB\Type\StringSet;
use tests\RavenDB\RemoteTestBase;
use RavenDB\Documents\Operations\PatchRequest;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Operations\PatchOperation;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;

class AdvancedPatchingTest extends RemoteTestBase
{
    public function testWithVariables(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $customType = new CustomType();
                $customType->setOwner("me");
                $session->store($customType, "customTypes/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $patchRequest = new PatchRequest();
            $patchRequest->setScript("this.owner = args.v1");

            $patchRequest->setValues(
                [
                    "v1" => "not-me"
                ]
            );

            $patchOperation = new PatchOperation("customTypes/1", null, $patchRequest);
            $store->operations()->send($patchOperation);

            $session = $store->openSession();
            try {
                /** @var CustomType $loaded */
                $loaded = $session->load(CustomType::class, "customTypes/1");
                $this->assertEquals("not-me", $loaded->getOwner());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanCreateDocumentsIfPatchingAppliedByIndex(): void
    {
        $store = $this->getDocumentStore();
        try {

            $newSession = $store->openSession();
            try {
                $type1 = new CustomType();
                $type1->setId("Item/1");
                $type1->setValue(1);

                $type2 = new CustomType();
                $type2->setId("Item/2");
                $type2->setValue(2);

                $newSession->store($type1);
                $newSession->store($type2);
                $newSession->saveChanges();
            } finally {
                $newSession->close();
            }

            $def1 = new IndexDefinition();
            $def1->setName("TestIndex");
            $set = new StringSet();
            $set->append("from doc in docs.CustomTypes select new { doc.value }");
            $def1->setMaps($set);

            $store->maintenance()->send(new PutIndexesOperation($def1));

            $session = $store->openSession();
            try {
                $session
                        ->advanced()->documentQuery(CustomType::class, "TestIndex", null, false)
                        ->waitForNonStaleResults()
                        ->toList();
            } finally {
                $session->close();
            }

            $operation = $store->operations()->sendAsync(new PatchByQueryOperation("FROM INDEX 'TestIndex' WHERE value = 1 update { put('NewItem/3', {'copiedValue': this.value });}"));
            $operation->waitForCompletion();

            $session = $store->openSession();
            try {
                $jsonDocument = $session->load(null, "NewItem/3");
                $this->assertEqualsWithDelta(1.0, $jsonDocument->copiedValue, 0.0001);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    private string $SAMPLE_SCRIPT = "this.comments.splice(2, 1);\n" .
            "    this.owner = 'Something new';\n" .
            "    this.value++;\n" .
            "    this.newValue = \"err!!\";\n" .
            "    this.comments = this.comments.map(function(comment) {\n" .
            "        return (comment == \"one\") ? comment + \" test\" : comment;\n" .
            "    });";

    public function testCanApplyBasicScriptAsPatch(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $test = new CustomType();
                $test->setId("someId");
                $test->setOwner("bob");
                $test->setValue(12143);
                $test->setComments(["one", "two", "seven"]);

                $session->store($test);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $store->operations()->send(new PatchOperation("someId", null, PatchRequest::forScript($this->SAMPLE_SCRIPT)));

            $session = $store->openSession();
            try {
                /** @var CustomType $result */
                $result = $session->load(CustomType::class, "someId");

                $this->assertEquals("Something new", $result->getOwner());
                $this->assertCount(2,  $result->getComments());
                $this->assertEquals("one test", $result->getComments()[0]);
                $this->assertEquals("two", $result->getComments()[1]);
                $this->assertEquals(12144, $result->getValue());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanDeserializeModifiedDocument(): void
    {
        $store = $this->getDocumentStore();
        try {
            $customType = new CustomType();
            $customType->setOwner("somebody@somewhere.com");
            $session = $store->openSession();
            try {
                $session->store($customType, "doc");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $patch1 = new PatchOperation("doc", null, PatchRequest::forScript("this.owner = '123';"));

            $result = $store->operations()->send(CustomType::class, $patch1);

            $this->assertTrue($result->getStatus()->isPatched());
            $this->assertEquals('123', $result->getDocument()->getOwner());

            $patch2 = new PatchOperation("doc", null, PatchRequest::forScript("this.owner = '123';"));

            $result = $store->operations()->send(CustomType::class, $patch2);

            $this->assertTrue($result->getStatus()->isNotModified());
            $this->assertEquals('123', $result->getDocument()->getOwner());

        } finally {
            $store->close();
        }
    }
}
