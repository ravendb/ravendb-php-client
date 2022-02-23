<?php

namespace RavenDB\Documents\Operations;

use RavenDB\ServerWide\Operations\Certificates\CertificateMetadataArray;

class GetCertificatesMetadataResponse extends ResultResponse
{
    public function __construct()
    {
        parent::__construct(CertificateMetadataArray::class);
    }
}
