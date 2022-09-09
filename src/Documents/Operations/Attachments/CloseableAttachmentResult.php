<?php

namespace RavenDB\Documents\Operations\Attachments;

use RavenDB\Http\HttpResponse;

class CloseableAttachmentResult
{
    private ?AttachmentDetails $details = null;
    private ?HttpResponse $response = null;

    public function __construct(HttpResponse $response, ?AttachmentDetails $details)
    {
        $this->details = $details;
        $this->response = $response;
    }

    public function getData(): string
    {
        return $this->response->getContent();
    }

    public function getDetails(): ?AttachmentDetails
    {
        return $this->details;
    }

    public function close(): void
    {

    }
}
