<?php

namespace tests\RavenDB;

use RavenDB\Auth\AuthOptions;
use RavenDB\Auth\CertificateType;
use RavenDB\Auth\PfxCertificate;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Type\StringArray;
use RavenDB\Type\Url;
use RuntimeException;
use tests\RavenDB\Driver\RavenServerLocator;

class TestSecuredServiceLocator extends RavenServerLocator
{
    public const ENV_SERVER_CA_PATH = "RAVENDB_PHP_TEST_CA_PATH";

    public const ENV_SERVER_CERTIFICATE_PATH = "RAVENDB_PHP_TEST_CERTIFICATE_PATH";
    public const ENV_HTTPS_SERVER_URL = "RAVENDB_PHP_TEST_HTTPS_SERVER_URL";

    public const ENV_CLIENT_CERT_PATH = "RAVENDB_TEST_CLIENT_CERT_PATH";
    public const ENV_CLIENT_CERT_PASSPHRASE = "RAVENDB_TEST_CLIENT_CERT_PASSPHRASE";

    public function getCommandArguments(): StringArray
    {
        try {
            $arguments = new StringArray();

            $arguments->append("--Security.Certificate.Path=" . $this->getServerCertificatePath());
            $arguments->append("--ServerUrl=" . $this->_getHttpsServerUrl());
            $arguments->append("--ServerUrl.Tcp=" . $this->_getHttpsServerTcpUrl());
            $arguments->append("--Features.Availability=Experimental");

             return $arguments;
        } catch (\Throwable $e) {
            throw new RuntimeException($e);
        }
    }

    /**
     * @throws IllegalStateException
     */
    private function _getHttpsServerUrl(): string
    {
        $httpsServerUrl = getenv(self::ENV_HTTPS_SERVER_URL);
        if (empty($httpsServerUrl)) {
            throw new IllegalStateException("Unable to find RavenDB https server url. " .
                    "Please make sure " . self::ENV_HTTPS_SERVER_URL . " environment variable is set and is valid " .
                    "(current value = " . $httpsServerUrl . ")");
        }

        return $httpsServerUrl;
    }

    private function _getHttpsServerTcpUrl(): string
    {
        $url = new Url($this->_getHttpsServerUrl());
        $host = $url->getHost();
        return "tcp://" . $host . ":38882";
    }


    /**
     * @throws IllegalStateException
     */
    public function getServerCertificatePath(): string
    {
        $certificatePath = getenv(self::ENV_SERVER_CERTIFICATE_PATH);
        if (empty($certificatePath)) {
            throw new IllegalStateException("Unable to find RavenDB server certificate path. " .
                    "Please make sure " . self::ENV_SERVER_CERTIFICATE_PATH . " environment variable is set and is valid " .
                    "(current value = " . $certificatePath . ")");
        }

        return $certificatePath;
    }

    public function getServerCaPath(): string
    {
        return getenv(self::ENV_SERVER_CA_PATH);
    }

    public function getClientAuthOptions(): AuthOptions
    {
        $clientCertPath = getenv(self::ENV_CLIENT_CERT_PATH);
        $clientCertPass = getenv(self::ENV_CLIENT_CERT_PASSPHRASE);

        $serverCaCertPath = getenv(self::ENV_SERVER_CA_PATH);

        if (empty($clientCertPath)) {
            $options = new AuthOptions();
            $options->setCertificatePath($this->getServerCertificatePath());
            $options->setType(CertificateType::pfx());
            return $options;
        }

        $options = new AuthOptions();

        $options->setType(CertificateType::pem());
        $options->setCertificatePath($clientCertPath);
        $options->setPassword($clientCertPass);
        $options->setCaPath($serverCaCertPath);

        return $options;
    }
}
