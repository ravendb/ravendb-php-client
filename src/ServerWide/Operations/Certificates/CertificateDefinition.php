<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Http\ResultInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class CertificateDefinition extends CertificateMetadata implements ResultInterface
{
    /** @SerializedName("Certificate") */
    private ?string $certificate = null;

    /** @SerializedName("Password") */
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
