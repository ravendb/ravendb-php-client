<?php

namespace tests\RavenDB\Infrastructure;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Warning;

class TestRunGuard
{
    public static string $ENV_RAVEN_LICENSE = "RAVEN_LICENSE";
    public static string $SERVER_VERSION = "SERVER_VERSION";

    public static int $MAJOR_VERSION = 0;
    public static int $MINOR_VERSION = 1;

    public static function disableTestIfLicenseNotAvailable(TestCase $testCase): void
    {
        $ravenLicense = getenv(self::$ENV_RAVEN_LICENSE);
        if (empty($ravenLicense)) {
            $testCase->markTestSkipped("Test disabled on Pull Request. License not available.");
        }
    }

    public static function disableTestIfLicenseNotAvailableForV6(TestCase $testCase): void
    {
        $ravenLicense = getenv(self::$ENV_RAVEN_LICENSE);
        if (!empty($ravenLicense)) {
            return;
        }

        $version = self::getServerVersion();
        if ($version[self::$MAJOR_VERSION] < 6) {
            return;
        }

        $testCase->markTestSkipped("Test disabled on Pull Request. License not available.");
    }

    public static function disableTestForRaven52(TestCase $testCase): void
    {
        if (self::isServerVersion52()) {
            $testCase->markTestSkipped("Test disabled for RavenDB version 5.2.");
        }
    }

    public static function isServerVersion52(): bool
    {
        $versionString = self::getServerVersionAsString();

        return $versionString == '5.2';
    }

    public static function disableTestForRaven6AndLater(TestCase $testCase): void
    {
        if (self::isServerVersionGreaterOrEqualThan60()) {
            $testCase->markTestSkipped("Test disabled for RavenDB version greater than 6.0");
        }
    }

    public static function isServerVersionGreaterOrEqualThan70(): bool
    {
        $version = self::getServerVersion();
        return intval($version[self::$MAJOR_VERSION]) >= 7;
    }

    public static function disableTestForRaven7AndLater(TestCase $testCase): void
    {
        if (self::isServerVersionGreaterOrEqualThan70()) {
            $testCase->markTestSkipped("Test disabled for RavenDB version greater than 7.0");
        }
    }

    public static function isServerVersionGreaterOrEqualThan60(): bool
    {
        $version = self::getServerVersion();
        return intval($version[self::$MAJOR_VERSION]) >= 6;
    }

    public static function getServerVersionAsString(): string
    {
        // Server version saved in .env variable in a format: MAJOR.MINOR, for example: "5.4"
        $serverVersion = getenv(self::$SERVER_VERSION);

        if (!$serverVersion) {
            return throw new Warning('RavenDB SERVER_VERSION is not set in .env variables');
        }

        return $serverVersion;
    }

    /**
     * Version information in array
     *  - $version[0] - major version
     *  - $version[1] - minor version
     *
     * @return array with information about major and minor version
     */
    public static function getServerVersion(): array
    {
        $versionString = self::getServerVersionAsString();

        return explode('.', $versionString);
    }

}
