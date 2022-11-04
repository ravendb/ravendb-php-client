<?php

namespace tests\RavenDB\Test\Issues\RavenDB_13034Test;

use tests\RavenDB\RemoteTestBase;

use Exception;
use Throwable;
use RavenDB\Exceptions\ConcurrencyException;

class RavenDB_13034Test extends RemoteTestBase
{
    public function testExploringConcurrencyBehavior(): void
    {
        $store = $this->getDocumentStore();
        try {

            $s1 = $store->openSession();
            try {
                $user = new User();
                $user->setName("Nick");
                $user->setAge(99);
                $s1->store($user, "users/1-A");
                $s1->saveChanges();
            } finally {
                $s1->close();
            }

            $s2 = $store->openSession();
            try {
                $s2->advanced()->setUseOptimisticConcurrency(true);

                $u2 = $s2->load(get_class($user), "users/1-A");

                $s3 = $store->openSession();
                try {
                    $u3 = $s3->load(get_class($user), "users/1-A");
                    $this->assertNotSame($u3, $u2);

                    $u3->age--;
                    $s3->saveChanges();
                } finally {
                    $s3->close();
                }

                $u2->age++;

                $u2_2 = $s2->load(get_class($user), "users/1-A");
                $this->assertEquals($u2_2, $u2);
                $this->assertEquals(1, $s2->advanced()->getNumberOfRequests());

                try {
                    $s2->saveChanges();
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(ConcurrencyException::class, $exception);
                }

                $this->assertEquals(2, $s2->advanced()->getNumberOfRequests());

                $u2_3 = $s2->load(get_class($user), "users/1-A");
                $this->assertEquals($u2_3, $u2);
                $this->assertEquals(2, $s2->advanced()->getNumberOfRequests());

                try {
                    $s2->saveChanges();
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(ConcurrencyException::class, $exception);
                }

            } finally {
                $s2->close();
            }

            $s4 = $store->openSession();
            try {
                $u4 = $s4->load(get_class($user), "users/1-A");
                $this->assertEquals(98, $u4->getAge());
            } finally {
                $s4->close();
            }
        } finally {
            $store->close();
        }
    }
}
