<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Http\ResultInterface;
use RavenDB\Type\TypedArray;

class CertificateMetadataArray extends TypedArray implements ResultInterface
{
    public function __construct()
    {
        parent::__construct(CertificateMetadata::class);
    }
}
