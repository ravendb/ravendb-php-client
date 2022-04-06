<?php

namespace RavenDB\Auth;

use InvalidArgumentException;

class Certificate implements CertificateInterface
{
    protected string $certificate;
    protected string $ca;
    protected string $passphrase;

    protected function __construct(string $certificate, string $passphrase, string $ca)
    {
        $this->certificate = $certificate;
        $this->passphrase = $passphrase;
        $this->ca = $ca;
    }

    public static function createFromOptions(?AuthOptions $options): ?CertificateInterface
    {
        if (!$options) {
            return null;
        }

        $certificate = null;

        if ($options->getType()->isPem()) {
            $certificate = self::createPem($options->getCertificatePath(), $options->getPassword(), $options->getCaPath());
        }

        if ($options->getType()->isPfx()) {
            $certificate = self::createPfx($options->getCertificatePath(), $options->getPassword(), $options->getCaPath());
        }

        if ($certificate == null) {
            throw new InvalidArgumentException("Unsupported authOptions type: " . $options->getType()->getValue());
        }

        return $certificate;
    }

    public static function createPem(string $certificate, ?string $passphrase = null, ?string $ca = null): CertificateInterface
    {
        return new PemCertificate($certificate, $passphrase, $ca);
    }

    public static function createPfx(string $certificate, ?string $passphrase = null, ?string $ca = null): CertificateInterface
    {
        return new PfxCertificate($certificate, $passphrase, $ca);
    }

//    public toAgentOptions(): AgentOptions {
//        if (this._passphrase) {
//            return { passphrase: this._passphrase };
//        }
//
//        return {};
//    }
//
//    public toWebSocketOptions(): WebSocket.ClientOptions {
//        if (this._passphrase) {
//            return { passphrase: this._passphrase };
//        }
//
//        return {};
//    }
}
