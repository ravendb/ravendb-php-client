<?php

namespace tests\RavenDB\Test\Issues;

use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class RDBC_339Test extends RemoteTestBase
{
    /** @doesNotPerformAssertions */
    public function testInvalidAttachmentsFormat(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $u = new User();
                $u->setName("John");
                $session->store($u);

                $session->advanced()->attachments()->store($u, "data", implode(array_map("chr", [1, 2, 3])));
                $session->saveChanges();

                $u2 = new User();
                $u2->setName("Oz");
                $session->store($u2);
                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
