<?php

namespace RavenDB\Documents\Operations\Etl\ElasticSearch\Authentication;

use RavenDB\Type\StringArray;

class ElasticSearchCertificateAuthentication
{
    private ?StringArray $certificatesBase64 = null;

    public function getCertificatesBase64(): ?StringArray
    {
        return $this->certificatesBase64;
    }

    public function setCertificatesBase64(?StringArray $certificatesBase64): void
    {
        $this->certificatesBase64 = $certificatesBase64;
    }
}
