<?php

namespace tests\RavenDB\Test\Client\Attachments\UploadFileTest;

use RavenDB\Constants\DocumentsMetadata;
use Symfony\Component\Mime\Part\DataPart;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class UploadFileTest extends RemoteTestBase
{
    public function testUploadFileAsAttachment(): void
    {
        $store = $this->getDocumentStore();
        try {
            $attachmentName = "profile.png";
            $filePath = __DIR__ . '/image.png';
            $fileMD5 = md5_file($filePath);

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("RavenDB");

                $session->store($user, "users/1");
                $session->advanced()->attachments()->storeFile($user, $attachmentName, $filePath);

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

                $this->assertCount(1, $attachments);
                $this->assertEquals($attachmentName, $attachments[0]["Name"]);

                $content = $session->advanced()->attachments()->get($user, $attachmentName);
                $this->assertEquals($fileMD5, md5($content->getData()));

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
