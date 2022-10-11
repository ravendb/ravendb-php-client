<?php

namespace tests\RavenDB\Infrastructure\Graph;

class Dog
{
    private ?string $id = null;
    private ?string $name = null;
    private ?array $likes = null;
    private ?array $dislikes = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getLikes(): ?array
    {
        return $this->likes;
    }

    public function setLikes(?array $likes): void
    {
        $this->likes = $likes;
    }

    public function getDislikes(): ?array
    {
        return $this->dislikes;
    }

    public function setDislikes(?array $dislikes): void
    {
        $this->dislikes = $dislikes;
    }
}
