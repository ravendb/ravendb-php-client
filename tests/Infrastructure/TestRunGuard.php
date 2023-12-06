<?php

namespace tests\RavenDB\Infrastructure;

use PHPUnit\Framework\TestCase;

class TestRunGuard
{
    public static string $ENV_RAVEN_LICENSE = "RAVEN_LICENSE";
    public static string $SERVER_VERSION = "SERVER_VERSION";

    public static function disableTestIfLicenseNotAvailable(TestCase $testCase): void
    {
        $ravenLicense = getenv(self::$ENV_RAVEN_LICENSE);
        if (empty($ravenLicense)) {
            $testCase->markTestSkipped("Test disabled on Pull Request. License not available.");
        }
    }

    public static function disableTestForRaven52(TestCase $testCase): void
    {
        $serverVersion = getenv(self::$SERVER_VERSION);
        if ($serverVersion == '5.2') {
            $testCase->markTestSkipped("Test disabled for RavenDB version 5.2.");
        }
    }
}
