<?php

namespace tests\RavenDB\Test\Client\Issues\RavenDB_13452Test;

use tests\RavenDB\RemoteTestBase;

class RavenDB_13452Test extends RemoteTestBase
{
    public function testCanModifyDictionaryWithPatch_Add(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $item = new Item();
                $item->setValues([
                    "Key1" => "Value1",
                    "Key2" => "Value2"
                ]);

                $session->store($item, "items/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {

                $item = $session->load(null, "items/1");

                print_r($item);




                $item = $session->load(Item::class, "items/1");
                $session->advanced()->patchObject($item, "values", function ($dict) {
                    $dict->put("Key3", "Value3");
                });
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var Item $item */
                $item = $session->load(Item::class, "items/1");
                $values = $item->getValues();

                $this->assertNotNull($values);
                $this->assertCount(3, $values);

                $this->assertIsString($values['Key1']);
                $this->assertEquals('Value1', $values['Key1']);
                $this->assertIsString($values['Key2']);
                $this->assertEquals('Value2', $values['Key2']);
                $this->assertIsString($values['Key3']);
                $this->assertEquals('Value3', $values['Key3']);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanModifyDictionaryWithPatch_Remove(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $item = new Item();
                $item->setValues([
                    "Key1" => "Value1",
                    "Key2" => "Value2",
                    "Key3" => "Value3"
                ]);

                $session->store($item, "items/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $item = $session->load(Item::class, "items/1");
                $session->advanced()->patchObject($item, "values",  function($dict) {
                    $dict->remove("Key2");
                });
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var Item $item */
                $item = $session->load(Item::class, "items/1");
                $values = $item->getValues();

                $this->assertNotNull($values);
                $this->assertCount(2, $values);

                $this->assertIsString($values['Key1']);
                $this->assertEquals('Value1', $values['Key1']);
                $this->assertIsString($values['Key3']);
                $this->assertEquals('Value3', $values['Key3']);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
