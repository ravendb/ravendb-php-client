<?php

namespace RavenDB\Documents\Operations\Attachments;

use RavenDB\Type\TypedArray;

class AttachmentNameArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(AttachmentName::class);
    }
}
