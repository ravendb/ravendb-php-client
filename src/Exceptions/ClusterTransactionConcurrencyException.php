<?php

namespace RavenDB\Exceptions;

class ClusterTransactionConcurrencyException extends ConcurrencyException
{
    private ?ConcurrencyViolationArray $concurrencyViolations = null;

    public function __construct(?string $message, ?\Throwable $cause = null) {
        parent::__construct($message, $cause);
    }

    public function getConcurrencyViolations(): ConcurrencyViolationArray
    {
        return $this->concurrencyViolations;
    }

    public function setConcurrencyViolations(?ConcurrencyViolationArray $concurrencyViolations): void
    {
        $this->concurrencyViolations = $concurrencyViolations;
    }
}
