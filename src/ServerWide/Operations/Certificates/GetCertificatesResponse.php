<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Http\ResultInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class GetCertificatesResponse implements ResultInterface
{
    /** @SerializedName("Results") */
    private CertificateDefinitionArray $results;

    public function getResults(): CertificateDefinitionArray
    {
        return $this->results;
    }

    public function setResults(CertificateDefinitionArray $results): void
    {
        $this->results = $results;
    }
}
