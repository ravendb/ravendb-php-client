<?php

namespace RavenDB\Documents\Session;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpResponse;
use RavenDB\Primitives\EventArgs;

class SucceedRequestEventArgs extends EventArgs
{
    private ?string $database = null;
    private ?string $url = null;
    private ?HttpResponse $response = null;
    private ?HttpRequest $request = null;
    private ?int $attemptNumber = null;

    public function __construct(?string $database, ?string $url, ?HttpResponse $response, ?HttpRequest $request, ?int $attemptNumber)
    {
        $this->database = $database;
        $this->url = $url;
        $this->response = $response;
        $this->request = $request;
        $this->attemptNumber = $attemptNumber;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getResponse(): ?HttpResponse
    {
        return $this->response;
    }

    public function getRequest(): ?HttpRequest
    {
        return $this->request;
    }

    public function getAttemptNumber(): ?int
    {
        return $this->attemptNumber;
    }
}
