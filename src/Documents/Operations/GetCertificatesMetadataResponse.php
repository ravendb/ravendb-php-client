<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\ResultInterface;
use RavenDB\ServerWide\Operations\Certificates\CertificateMetadataArray;
use Symfony\Component\Serializer\Annotation\SerializedName;

class GetCertificatesMetadataResponse implements ResultInterface
{
    /** @SerializedName("Results") */
    private CertificateMetadataArray $results;

    public function getResults(): CertificateMetadataArray
    {
        return $this->results;
    }

    public function setResults(CertificateMetadataArray $results): void
    {
        $this->results = $results;
    }
}
