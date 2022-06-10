<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15531Test;

use tests\RavenDB\RemoteTestBase;

class RavenDB_15531Test extends RemoteTestBase
{
    public function testShouldWork(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $doc = new SimpleDoc();
                $doc->setId("TestDoc");
                $doc->setName("State1");

                $session->store($doc);
                $session->saveChanges();

                $doc->setName("State2");
                $changes1 = $session->advanced()->whatChanged();

                $changes = array_key_exists('TestDoc', $changes1) ? $changes1['TestDoc'] : null;

                $this->assertNotNull($changes);
                $this->assertCount(1, $changes);

                $this->assertTrue($changes[0]->getChange()->isFieldChanged());
                $this->assertEquals('name', $changes[0]->getFieldName());
                $this->assertEquals('State1', $changes[0]->getFieldOldValue());
                $this->assertEquals('State2', $changes[0]->getFieldNewValue());

                $session->saveChanges();

                $doc->setName("State3");

                $changes1 = $session->advanced()->whatChanged();

                $changes = $changes1['TestDoc'];

                $this->assertNotNull($changes);
                $this->assertCount(1, $changes);

                $this->assertTrue($changes[0]->getChange()->isFieldChanged());
                $this->assertEquals('name', $changes[0]->getFieldName());
                $this->assertEquals('State2', $changes[0]->getFieldOldValue());
                $this->assertEquals('State3', $changes[0]->getFieldNewValue());

                $session->advanced()->refresh($doc);

                $doc->setName("State4");
                $changes1 = $session->advanced()->whatChanged();

                $changes = $changes1['TestDoc'];

                $this->assertNotNull($changes);
                $this->assertCount(1, $changes);

                $this->assertTrue($changes[0]->getChange()->isFieldChanged());
                $this->assertEquals('name', $changes[0]->getFieldName());
                $this->assertEquals('State2', $changes[0]->getFieldOldValue());
                $this->assertEquals('State4', $changes[0]->getFieldNewValue());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
