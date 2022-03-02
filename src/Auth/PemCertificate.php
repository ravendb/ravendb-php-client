<?php

namespace RavenDB\Auth;

use InvalidArgumentException;
use RavenDB\Utils\StringUtils;

class PemCertificate extends Certificate
{
    private const CERT_TOKEN = "CERTIFICATE";
    private const KEY_TOKEN = "RSA PRIVATE KEY";

    protected ?string $key = null;

    public function __construct(string $certificate, string $passphrase, string $ca)
    {
        parent::__construct($certificate, $passphrase, $ca);

        $this->key = $this->fetchPart(self::KEY_TOKEN);
        $this->certificate = $this->fetchPart(self::CERT_TOKEN);

        if (!$this->key && !$this->certificate) {
            throw new InvalidArgumentException('Invalid .pem certificate provided');
        }
    }

//    public toAgentOptions(): AgentOptions {
//        const result = super.toAgentOptions();
//        return Object.assign(result, {
//            cert: this._certificate,
//            key: this._key,
//            ca: this._ca
//        });
//    }
//
//    public toWebSocketOptions(): WebSocket.ClientOptions {
//        const result = super.toWebSocketOptions();
//        return Object.assign(result, {
//            cert: this._certificate,
//            key: this._key,
//            ca: this._ca
//        });
//    }

    protected function fetchPart(string $token): ?string
    {
        $cert = $this->certificate;
        $prefixSuffix = "-----";
        $beginMarker = "$prefixSuffix . 'BEGIN ' . $token . $prefixSuffix";
        $endMarker = $prefixSuffix . 'END ' . $token . $prefixSuffix;

        if (str_contains($cert, $beginMarker) && str_contains($cert, $endMarker)) {
            $part = substr($cert, strpos($cert, $beginMarker), strpos($cert, $endMarker) + strlen($endMarker));

            if (!StringUtils::isNullOrWhitespace($part)) {
                return $part;
            }
        }

        return null;
    }
}
