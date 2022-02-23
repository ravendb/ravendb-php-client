<?php

namespace tests\RavenDB;

use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Type\StringArray;
use RavenDB\Type\Url;
use RuntimeException;
use tests\RavenDB\Driver\RavenServerLocator;

class TestSecuredServiceLocator extends RavenServerLocator
{
        public const ENV_CERTIFICATE_PATH = "RAVENDB_JAVA_TEST_CERTIFICATE_PATH";

        public const ENV_TEST_CA_PATH = "RAVENDB_JAVA_TEST_CA_PATH";

        public const ENV_HTTPS_SERVER_URL = "RAVENDB_JAVA_TEST_HTTPS_SERVER_URL";

    public function getCommandArguments(): StringArray
        {
            $httpsServerUrl = $this->getHttpsServerUrl();

            try {
                $url = new Url($httpsServerUrl);
                $host = $url->getHost();
                $tcpServerUrl = "tcp://" . $host . ":38882";

                $arguments = new StringArray();

                $arguments->append("--Security.Certificate.Path=" . $this->getServerCertificatePath());
                $arguments->append("--ServerUrl=" . $this->getHttpsServerUrl());
                $arguments->append("--ServerUrl.Tcp=" . $tcpServerUrl);

                 return $arguments;
            } catch (\Throwable $e) {
                throw new RuntimeException($e);
            }
        }

    /**
     * @throws IllegalStateException
     */
    private function getHttpsServerUrl(): string
      {
            $httpsServerUrl = getenv(self::ENV_HTTPS_SERVER_URL);
            if (empty($httpsServerUrl)) {
                throw new IllegalStateException("Unable to find RavenDB https server url. " .
                        "Please make sure " . self::ENV_HTTPS_SERVER_URL . " environment variable is set and is valid " .
                        "(current value = " . $httpsServerUrl . ")");
            }

            return $httpsServerUrl;
        }


    /**
     * @throws IllegalStateException
     */
    public function getServerCertificatePath(): string
    {
        $certificatePath = getenv(self::ENV_CERTIFICATE_PATH);
        if (empty($certificatePath)) {
            throw new IllegalStateException("Unable to find RavenDB server certificate path. " .
                    "Please make sure " . self::ENV_CERTIFICATE_PATH . " environment variable is set and is valid " .
                    "(current value = " . $certificatePath . ")");
        }

        return $certificatePath;
    }

    public function getServerCaPath(): string
    {
        return getenv(self::ENV_TEST_CA_PATH);
    }
}
