<?php

namespace RavenDB\Auth;

class AuthOptions
{
    private ?CertificateType $type = null;
    private ?string $certificatePath = null;
    private ?string $password = null;
    private ?string $caPath = null;
    private ?string $caFile = null;

    public function __construct()
    {
    }

    public static function pem(string $certificatePath, string $password = null, string $caPath = null): AuthOptions
    {
        $options = new self();

        $options->type = CertificateType::pem();
        $options->setCertificatePath($certificatePath);
        $options->setPassword($password);

        if (is_dir($caPath)) {
            $options->setCaPath($caPath);
        } else {
            $options->setCaFile($caPath);
        }

        return $options;
    }

    public function getType(): ?CertificateType
    {
        return $this->type;
    }

    public function setType(?CertificateType $type): void
    {
        $this->type = $type;
    }

    public function getCertificatePath(): ?string
    {
        return $this->certificatePath;
    }

    public function setCertificatePath(?string $certificatePath): void
    {
        $this->certificatePath = $certificatePath;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getCaPath(): ?string
    {
        return $this->caPath;
    }

    public function setCaPath(?string $caPath): void
    {
        $this->caPath = $caPath;
    }

    public function getCaFile(): ?string
    {
        return $this->caFile;
    }

    public function setCaFile(?string $caFile): void
    {
        $this->caFile = $caFile;
    }
}
