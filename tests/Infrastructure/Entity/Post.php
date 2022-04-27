<?php

namespace tests\RavenDB\Infrastructure\Entity;

// !status: DONE
use DateTimeInterface;

class Post
{
    private ?string $id = null;
    private ?string $title = null;
    private ?string $desc = null;
    private ?PostArray $comments = null;
    private ?string $attachmentIds = null;
    private ?DateTimeInterface $createdAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDesc(): ?string
    {
        return $this->desc;
    }

    public function setDesc(?string $desc): void
    {
        $this->desc = $desc;
    }

    public function getComments(): ?PostArray
    {
        return $this->comments;
    }

    public function setComments(?PostArray $comments): void
    {
        $this->comments = $comments;
    }

    public function getAttachmentIds(): ?string
    {
        return $this->attachmentIds;
    }

    public function setAttachmentIds(?string $attachmentIds): void
    {
        $this->attachmentIds = $attachmentIds;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
