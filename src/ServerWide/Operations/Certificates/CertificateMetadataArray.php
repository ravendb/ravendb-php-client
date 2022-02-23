<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Type\TypedArray;

class CertificateMetadataArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(CertificateMetadata::class);
    }
}
