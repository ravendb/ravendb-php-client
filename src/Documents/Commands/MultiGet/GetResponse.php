<?php

namespace RavenDB\Documents\Commands\MultiGet;

use RavenDB\Constants\HttpStatusCode;
use RavenDB\Type\Duration;
use RavenDB\Type\ExtendedArrayObject;

class GetResponse
{
    private ?Duration $elapsed = null;
    private ?array $result = null;
    private ?ExtendedArrayObject $headers = null;
    private ?int $statusCode = null;
    private bool $forceRetry = false;

    public function __construct()
    {
        $this->headers = new ExtendedArrayObject();
        $this->headers->setKeysCaseInsensitive(true);
    }

    public function getElapsed(): ?Duration
    {
        return $this->elapsed;
    }

    public function setElapsed(?Duration $elapsed): void
    {
        $this->elapsed = $elapsed;
    }

    public function getResult(): ?array
    {
        return $this->result;
    }

    public function setResult(?array $result): void
    {
        $this->result = $result;
    }

    public function getHeaders(): ?ExtendedArrayObject
    {
        return $this->headers;
    }

    public function setHeaders(?ExtendedArrayObject $headers): void
    {
        $this->headers = $headers;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(?int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function isForceRetry(): bool
    {
        return $this->forceRetry;
    }

    public function setForceRetry(bool $forceRetry): void
    {
        $this->forceRetry = $forceRetry;
    }

    /**
     * @return bool Method used to check if request has errors.
     */
    public function requestHasErrors(): bool
    {
        switch ($this->statusCode) {
            case 0:
            case HttpStatusCode::OK:
            case HttpStatusCode::CREATED:
            case HttpStatusCode::NON_AUTHORITATIVE_INFORMATION:
            case HttpStatusCode::NO_CONTENT:
            case HttpStatusCode::NOT_MODIFIED:
            case HttpStatusCode::NOT_FOUND:
                return false;
            default:
                return true;
        }
    }

}
