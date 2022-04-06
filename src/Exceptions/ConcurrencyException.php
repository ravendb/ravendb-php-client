<?php

namespace RavenDB\Exceptions;

// !status: DONE
class ConcurrencyException extends ConflictException
{
    private int $expectedETag;
    private int $actualETag;
    private string $expectedChangeVector;
    private string $actualChangeVector;

    public function __construct(string $message, ?\Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }

    public function getExpectedETag(): int
    {
        return $this->expectedETag;
    }

    public function setExpectedETag(int $expectedETag): void
    {
        $this->expectedETag = $expectedETag;
    }

    public function getActualETag(): int
    {
        return $this->actualETag;
    }

    public function setActualETag(int $actualETag): void
    {
        $this->actualETag = $actualETag;
    }

    public function getExpectedChangeVector(): string
    {
        return $this->expectedChangeVector;
    }

    public function setExpectedChangeVector(string $expectedChangeVector): void
    {
        $this->expectedChangeVector = $expectedChangeVector;
    }

    public function getActualChangeVector(): string
    {
        return $this->actualChangeVector;
    }

    public function setActualChangeVector(string $actualChangeVector): void
    {
        $this->actualChangeVector = $actualChangeVector;
    }


}
