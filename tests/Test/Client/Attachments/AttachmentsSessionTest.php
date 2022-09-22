<?php

namespace tests\RavenDB\Test\Client\Attachments;

use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Commands\Batches\DeleteCommandData;
use RavenDB\Documents\Operations\Attachments\AttachmentName;
use RavenDB\Documents\Operations\Attachments\DeleteAttachmentOperation;
use RavenDB\Exceptions\IllegalStateException;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class AttachmentsSessionTest extends RemoteTestBase
{
    public function testPutAttachments(): void
    {
        $store = $this->getDocumentStore();
        try {
            $names = ["profile.png", "background-photo.jpg", "fileNAME_#$1^%_בעברית.txt"];

            $session = $store->openSession();
            try {
                $profileStream = implode(array_map("chr", [1, 2, 3]));
                $backgroundStream = implode(array_map("chr", [10, 20, 30, 40, 50]));
                $fileStream = implode(array_map("chr", [1, 2, 3, 4, 5]));

                $user = new User();
                $user->setName("Fitzchak");

                $session->store($user, "users/1");

                $session->advanced()->attachments()->store("users/1", $names[0], $profileStream, "image/png");
                $session->advanced()->attachments()->store($user, $names[1], $backgroundStream, "ImGgE/jPeG");
                $session->advanced()->attachments()->store($user, $names[2], $fileStream);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1");
                $metadata = $session->advanced()->getMetadataFor($user);
                $this->assertEquals("HasAttachments", $metadata->get(DocumentsMetadata::FLAGS));

                $attachments = $metadata->get(DocumentsMetadata::ATTACHMENTS);

                $this->assertCount(3, $attachments);

                sort($names);

                for ($i = 0; $i < count($names); $i++) {
                    $name = $names[$i];
                    $attachment = $attachments[$i];

                    $this->assertEquals($name, $attachment["Name"]);
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testThrowIfStreamIsUseTwice(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $stream = implode(array_map("chr", [1, 2, 3]));

                $user = new User();
                $user->setName("Fitzchak");
                $session->store($user, "users/1");

                $session->advanced()->attachments()->store($user, "profile", $stream, "image/png");
                $session->advanced()->attachments()->store($user, "other", $stream);

                $this->expectException(IllegalStateException::class);

                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testThrowWhenTwoAttachmentsWithTheSameNameInSession(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $stream = implode(array_map("chr", [1, 2, 3]));
                $stream2 = implode(array_map("chr", [1, 2, 3, 4, 5]));

                $user = new User();
                $user->setName("Fitzchak");
                $session->store($user, "users/1");

                $session->advanced()->attachments()->store($user, "profile", $stream, "image/png");

                $this->expectException(IllegalStateException::class);
                $session->advanced()->attachments()->store($user, "profile", $stream2);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testPutDocumentAndAttachmentAndDeleteShouldThrow(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $profileStream = implode(array_map("chr", [1, 2, 3]));

                $user = new User();
                $user->setName("Fitzchak");
                $session->store($user, "users/1");

                $session->advanced()->attachments()->store($user, "profile.png", $profileStream, "image/png");

                $session->delete($user);

                $this->expectException(IllegalStateException::class);
                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testDeleteAttachments(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Fitzchak");
                $session->store($user, "users/1");

                $stream1 = implode(array_map("chr", [1, 2, 3]));
                $stream2 = implode(array_map("chr", [1, 2, 3, 4, 5, 6]));
                $stream3 = implode(array_map("chr", [1, 2, 3, 4, 5, 6, 7, 8, 9]));
                $stream4 = implode(array_map("chr", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]));

                $session->advanced()->attachments()->store($user, "file1", $stream1, "image/png");
                $session->advanced()->attachments()->store($user, "file2", $stream2, "image/png");
                $session->advanced()->attachments()->store($user, "file3", $stream3, "image/png");
                $session->advanced()->attachments()->store($user, "file4", $stream4, "image/png");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1");

                // test get attachment by its name
                $attachmentResult = $session->advanced()->attachments()->get("users/1", "file2");
                try {
                    $this->assertEquals("file2", $attachmentResult->getDetails()->getName());
                } finally {
                    $attachmentResult->close();
                }

                $session->advanced()->attachments()->delete("users/1", "file2");
                $session->advanced()->attachments()->delete($user, "file4");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1");
                $metadata = $session->advanced()->getMetadataFor($user);
                $this->assertEquals("HasAttachments", strval($metadata->get(DocumentsMetadata::FLAGS)));

                $attachments = $metadata->get(DocumentsMetadata::ATTACHMENTS);

                $this->assertCount(2, $attachments);

                $result = $session->advanced()->attachments()->get("users/1", "file1");
                try {
                    $file1Bytes = $result->getData();
                    $this->assertEquals(3, strlen($file1Bytes));
                } finally {
                    $result->close();
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testDeleteAttachmentsUsingCommand(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Fitzchak");
                $session->store($user, "users/1");

                $stream1 = implode(array_map("chr", [1, 2, 3]));
                $stream2 = implode(array_map("chr", [1, 2, 3, 4, 5, 6]));

                $session->advanced()->attachments()->store($user, "file1", $stream1, "image/png");
                $session->advanced()->attachments()->store($user, "file2", $stream2, "image/png");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $store->operations()->send(new DeleteAttachmentOperation("users/1", "file2"));

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1");
                $metadata = $session->advanced()->getMetadataFor($user);
                $this->assertEquals("HasAttachments", strval($metadata->get(DocumentsMetadata::FLAGS)));

                $attachments = $metadata->get(DocumentsMetadata::ATTACHMENTS);

                $this->assertCount(1, $attachments);

                $result = $session->advanced()->attachments()->get("users/1", "file1");
                try {
                    $file1Bytes = $result->getData();
                    $this->assertEquals(3, strlen($file1Bytes));
                } finally {
                    $result->close();
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    /** @doesNotPerformAssertions */
    public function testGetAttachmentReleasesResources(): void
    {
        $count = 30;

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $session->store($user, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            for ($i = 0; $i < $count; $i++) {
                $session = $store->openSession();
                try {
                    $stream1 = implode(array_map("chr", [1, 2, 3]));
                    $session->advanced()->attachments()->store("users/1", "file" . $i, $stream1, "image/png");
                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            for ($i = 0; $i < $count; $i++) {
                $session = $store->openSession();
                try {
                    $result = $session->advanced()->attachments()->get("users/1", "file" . $i);
                    try {
                        // don't read data as it marks entity as consumed
                    } finally {
                        $result->close();
                    }
                } finally {
                    $session->close();
                }
            }
        } finally {
            $store->close();
        }
    }

    /** @doesNotPerformAssertions */
    public function testDeleteDocumentAndThanItsAttachments_ThisIsNoOpButShouldBeSupported(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Fitzchak");
                $session->store($user, "users/1");

                $stream = implode(array_map("chr", [1, 2, 3]));

                $session->advanced()->attachments()->store($user, "file", $stream, "image/png");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1");

                $session->delete($user);
                $session->advanced()->attachments()->delete($user, "file");
                $session->advanced()->attachments()->delete($user, "file"); // this should be no-op

                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    /** @doesNotPerformAssertions */
    public function testDeleteDocumentByCommandAndThanItsAttachments_ThisIsNoOpButShouldBeSupported(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Fitzchak");
                $session->store($user, "users/1");

                $stream = implode(array_map("chr", [1, 2, 3]));

                $session->advanced()->attachments()->store($user, "file", $stream, "image/png");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->defer(new DeleteCommandData("users/1", null));
                $session->advanced()->attachments()->delete("users/1", "file");
                $session->advanced()->attachments()->delete("users/1", "file");

                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testGetAttachmentNames(): void
    {
        $store = $this->getDocumentStore();
        try {
            $names = ["profile.png"];

            $session = $store->openSession();
            try {
                $profileStream = implode(array_map("chr", [1, 2, 3]));

                $user = new User();
                $user->setName("Fitzchak");
                $session->store($user, "users/1");

                $session->advanced()->attachments()->store("users/1", $names[0], $profileStream, "image/png");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, "users/1");
                $attachments = $session->advanced()->attachments()->getNames($user);

                $this->assertCount(1, $attachments);

                /** @var AttachmentName $attachment */
                $attachment = $attachments[0];

                $this->assertEquals("image/png", $attachment->getContentType());
                $this->assertEquals($names[0], $attachment->getName());
                $this->assertEquals(3, $attachment->getSize());

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testAttachmentExists(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $stream = implode(array_map("chr", [1, 2, 3]));

                $user = new User();
                $user->setName("Fitzchak");

                $session->store($user, "users/1");

                $session->advanced()->attachments()->store("users/1", "profile", $stream, "image/png");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $this->assertTrue($session->advanced()->attachments()->exists("users/1", "profile"));
                $this->assertFalse($session->advanced()->attachments()->exists("users/1", "background-photo"));
                $this->assertFalse($session->advanced()->attachments()->exists("users/2", "profile"));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
