<?php

namespace tests\RavenDB\Test\Client\MoreLikeThis\_MoreLikeThisTest;

abstract class Identity
{
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }
}
