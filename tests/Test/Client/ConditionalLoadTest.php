<?php

namespace tests\RavenDB\Test\Client;

use RavenDB\Documents\Session\ConditionalLoadResult;
use RavenDB\Exceptions\IllegalArgumentException;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class ConditionalLoadTest extends RemoteTestBase
{
    public function testConditionalLoad_CanGetDocumentById(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");
                $session->store($user, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $cv = "";
            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1");
                $cv = $session->advanced()->getChangeVectorFor($user);

                $this->assertNotNull($user);
                $this->assertEquals("RavenDB", $user->getName());

                $user->setName("RavenDB 5.1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var ConditionalLoadResult<User> $user */
                $user = $session->advanced()->conditionalLoad(User::class, "users/1", $cv);
                $this->assertEquals("RavenDB 5.1", $user->getEntity()->getName());

                $this->assertNotNull($user->getChangeVector());
                $this->assertNotEquals($cv, $user->getChangeVector());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testConditionalLoad_GetNotModifiedDocumentByIdShouldReturnNull(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");
                $session->store($user, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $cv = '';

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1");
                $this->assertNotNull($user);
                $this->assertEquals("RavenDB", $user->getName());

                $user->setName("RavenDB 5.1");
                $session->saveChanges();
                $cv = $session->advanced()->getChangeVectorFor($user);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var ConditionalLoadResult<User> $user */
                $user = $session->advanced()->conditionalLoad(User::class, "users/1", $cv);
                $this->assertNull($user->getEntity());
                $this->assertEquals($cv, $user->getChangeVector());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testConditionalLoad_NonExistsDocumentShouldReturnNull(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");
                $session->store($user, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $cv = '';
            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1");
                $this->assertNotNull($user);
                $this->assertEquals("RavenDB", $user->getName());

                $user->setName("RavenDB 5.1");
                $session->saveChanges();
                $cv = $session->advanced()->getChangeVectorFor($user);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $this->expectException(IllegalArgumentException::class);
                $session->advanced()->conditionalLoad(User::class, "users/2", null);

                /** @var ConditionalLoadResult<User>  $result */
                $result = $session->advanced()->conditionalLoad(User::class, "users/2", $cv);
                $this->assertNull($result->getEntity());
                $this->assertNull($result->getChangeVector());

                $this->assertTrue($session->advanced()->isLoaded("users/2"));

                $expected = $session->advanced()->getNumberOfRequests();
                $session->load(User::class, "users/2");

                $this->assertEquals($expected, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
