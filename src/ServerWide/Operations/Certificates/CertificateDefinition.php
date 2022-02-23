<?php

namespace RavenDB\ServerWide\Operations\Certificates;

// !status: DONE
class CertificateDefinition extends CertificateMetadata
{
    private ?string $certificate = null;
    private ?string $password = null;

    public function getCertificate(): ?string
    {
        return $this->certificate;
    }

    public function setCertificate(?string $certificate): void
    {
        $this->certificate = $certificate;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }
}
