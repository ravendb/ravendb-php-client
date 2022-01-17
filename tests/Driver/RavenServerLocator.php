<?php

namespace tests\RavenDB\Driver;

use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\UnsupportedOperationException;
use RavenDB\Type\StringArray;

class RavenServerLocator
{
      public const ENV_SERVER_PATH = "RAVENDB_JAVA_TEST_SERVER_PATH";

    /**
     * @throws IllegalStateException
     */
    public function getServerPath(): string
    {
        // @todo: load enviroment variable here
        $path = '';//System.getenv(ENV_SERVER_PATH);
        if (empty($path)) {
            throw new IllegalStateException("Unable to find RavenDB server path. " .
                    "Please make sure " . self::ENV_SERVER_PATH . " environment variable is set and is valid " .
                    "(current value = " . $path . ")");
        }

        return $path;
    }

    /**
     * @throws IllegalStateException
     */
    public function getCommand(): string
    {
        return $this->getServerPath();
    }

    public function getCommandArguments(): StringArray
    {
        $arguments = new StringArray();
        $arguments->append(null);
        return $arguments;
    }

    /**
     * @throws UnsupportedOperationException
     */
    public function getServerCertificatePath(): string
    {
        throw new UnsupportedOperationException();
    }

    /**
     * @throws UnsupportedOperationException
     */
    public function getServerCaPath(): string
    {
        throw new UnsupportedOperationException();
    }
}
