<?php

namespace tests\RavenDB\Test\Client\MoreLikeThis\_MoreLikeThisTest;

class Data extends Identity
{
    private ?string $body = null;
    private ?string $whitespaceAnalyzerField = null;
    private ?string $personId = null;

    public function __construct(?string $body = null)
    {
        $this->body = $body;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    public function getWhitespaceAnalyzerField(): ?string
    {
        return $this->whitespaceAnalyzerField;
    }

    public function setWhitespaceAnalyzerField(?string $whitespaceAnalyzerField): void
    {
        $this->whitespaceAnalyzerField = $whitespaceAnalyzerField;
    }

    public function getPersonId(): ?string
    {
        return $this->personId;
    }

    public function setPersonId(?string $personId): void
    {
        $this->personId = $personId;
    }
}
