<?php

namespace RavenDB\ServerWide\Operations\Certificates;

// !status: DONE
class GetCertificatesResponse
{
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
