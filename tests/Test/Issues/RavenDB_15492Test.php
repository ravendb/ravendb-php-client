<?php

namespace tests\RavenDB\Test\Issues;

use tests\RavenDB\RemoteTestBase;

class RavenDB_15492Test extends RemoteTestBase
{
    public function testWillCallOnBeforeDeleteWhenCallingDeleteById(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $called = false;
                $session->advanced()->addBeforeDeleteListener(function($sender, $event) use (&$called) {
                    $called = "users/1" == $event->getDocumentId();
                });

                $session->delete("users/1");
                $session->saveChanges();

                $this->assertTrue($called);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
