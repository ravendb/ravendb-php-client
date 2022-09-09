<?php

namespace RavenDB\Documents\Operations\Attachments;

class AttachmentIteratorResult
{
    private ?InputStream $stream = null;
    private ?AttachmentDetails $details = null;
}
