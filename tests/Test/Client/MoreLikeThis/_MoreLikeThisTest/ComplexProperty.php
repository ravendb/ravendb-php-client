<?php

namespace tests\RavenDB\Test\Client\MoreLikeThis\_MoreLikeThisTest;

class ComplexProperty
{
    private ?string $body = null;

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }
}
