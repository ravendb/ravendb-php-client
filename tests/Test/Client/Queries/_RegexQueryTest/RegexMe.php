<?php

namespace tests\RavenDB\Test\Client\Queries\_RegexQueryTest;

class RegexMe
{
    private ?String $text = null;

    public function __construct(?string $text = null)
    {
        $this->text = $text;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }
}
