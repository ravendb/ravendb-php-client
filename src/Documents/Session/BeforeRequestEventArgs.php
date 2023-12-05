<?php

namespace RavenDB\Documents\Session;

use RavenDB\Http\HttpRequest;
use RavenDB\Primitives\EventArgs;

class BeforeRequestEventArgs extends EventArgs
{
    private ?string $database = null;
    private ?string $url = null;
    private ?HttpRequest $request = null;
    private ?int $attemptNumber = null;

    public function __construct(?string $database, ?string $url, ?HttpRequest $request, ?int $attemptNumber)
    {
        $this->database = $database;
        $this->url = $url;
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

    public function getRequest(): ?HttpRequest
    {
        return $this->request;
    }

    public function getAttemptNumber(): ?int
    {
        return $this->attemptNumber;
    }
}
