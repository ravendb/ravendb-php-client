<?php

namespace RavenDB\Exceptions;

class ConcurrencyException extends ConflictException
{
    private ?int $expectedETag = null;
    private ?int $actualETag = null;
    private ?string $expectedChangeVector = null;
    private ?string $actualChangeVector = null;

    private ?string $id = null;

    public function __construct(string $message, ?\Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }

    /**
     * @deprecated Not used and will be removed and the next major version
     *
     * @return int expected etag
     */
    public function getExpectedETag(): int
    {
        return $this->expectedETag;
    }

    /**
     * @deprecated Not used and will be removed and the next major version
     *
     * @param int $expectedETag expected etag
     */
    public function setExpectedETag(int $expectedETag): void
    {
        $this->expectedETag = $expectedETag;
    }

    /**
     * @deprecated Not used and will be removed and the next major version
     *
     * @return int actual etag
     */
    public function getActualETag(): int
    {
        return $this->actualETag;
    }

    /**
     * @deprecated Not used and will be removed and the next major version
     *
     * @param int $actualETag actual etag
     */
    public function setActualETag(int $actualETag): void
    {
        $this->actualETag = $actualETag;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
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
