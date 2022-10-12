<?php

namespace tests\RavenDB\Infrastructure;

use PHPUnit\Framework\TestCase;

class DisableOnPullRequestCondition
{
    public static string $ENV_RAVEN_LICENSE = "RAVEN_LICENSE";

    public static function evaluateExecutionCondition(TestCase $testCase): void
    {
        $ravenLicense = getenv(self::$ENV_RAVEN_LICENSE);
        if (empty($ravenLicense)) {
            $testCase->markTestSkipped("Test disabled on Pull Request");
        }
    }
}
