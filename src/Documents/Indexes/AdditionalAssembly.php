<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Type\StringSet;
use RavenDB\Utils\StringUtils;

// !status: DONE
class AdditionalAssembly
{
    private ?string $assemblyName = null;
    private ?string $assemblyPath = null;
    private ?string $packageName = null;
    private ?string $packageVersion = null;
    private ?string $packageSourceUrl = null;
    private ?StringSet $usings = null;

    private function __construct()
    {
    }

    public function getAssemblyName(): ?string
    {
        return $this->assemblyName;
    }

    public function setAssemblyName(?string $assemblyName): void
    {
        $this->assemblyName = $assemblyName;
    }

    public function getAssemblyPath(): ?string
    {
        return $this->assemblyPath;
    }

    public function setAssemblyPath(?string $assemblyPath): void
    {
        $this->assemblyPath = $assemblyPath;
    }

    public function getPackageName(): ?string
    {
        return $this->packageName;
    }

    public function setPackageName(?string $packageName): void
    {
        $this->packageName = $packageName;
    }

    public function getPackageVersion(): ?string
    {
        return $this->packageVersion;
    }

    public function setPackageVersion(?string $packageVersion): void
    {
        $this->packageVersion = $packageVersion;
    }

    public function getPackageSourceUrl(): ?string
    {
        return $this->packageSourceUrl;
    }

    public function setPackageSourceUrl(?string $packageSourceUrl): void
    {
        $this->packageSourceUrl = $packageSourceUrl;
    }

    public function getUsings(): ?StringSet
    {
        return $this->usings;
    }

    public function setUsings(?StringSet $usings): void
    {
        $this->usings = $usings;
    }

    public static function onlyUsings(?StringSet $usings): AdditionalAssembly
    {
        if ($usings == null || $usings->isEmpty()) {
            throw new IllegalArgumentException("Using cannot be null or empty");
        }

        $additionalAssembly = new AdditionalAssembly();
        $additionalAssembly->setUsings($usings);
        return $additionalAssembly;
    }
    public static function fromRuntime(?string $assemblyName, ?StringSet $usings = null): AdditionalAssembly
    {
        if (StringUtils::isBlank($assemblyName)) {
            throw new IllegalArgumentException("AssemblyName cannot be null or whitespace.");
        }

        $additionalAssembly = new AdditionalAssembly();
        $additionalAssembly->setAssemblyName($assemblyName);
        $additionalAssembly->setUsings($usings);
        return $additionalAssembly;
    }

    public static function fromPath(string $assemblyPath, ?StringSet $usings = null): AdditionalAssembly
    {
        if (StringUtils::isBlank($assemblyPath)) {
            throw new IllegalArgumentException("AssemblyPath cannot be null or whitespace.");
        }

        $additionalAssembly = new AdditionalAssembly();
        $additionalAssembly->setAssemblyPath($assemblyPath);
        $additionalAssembly->setUsings($usings);
        return $additionalAssembly;
    }

    public static function fromNuGet(?string $packageName, ?string $packageVersion, ?string $packageSourceUrl = null, ?StringSet $usings = null): AdditionalAssembly
    {
        if (StringUtils::isBlank($packageName)) {
            throw new IllegalArgumentException("PackageName cannot be null or whitespace.");
        }
        if (StringUtils::isBlank($packageVersion)) {
            throw new IllegalArgumentException("PackageVersion cannot be null or whitespace.");
        }

        $additionalAssembly = new AdditionalAssembly();
        $additionalAssembly->setPackageName($packageName);
        $additionalAssembly->setPackageVersion($packageVersion);
        $additionalAssembly->setPackageSourceUrl($packageSourceUrl);
        $additionalAssembly->setUsings($usings);
        return $additionalAssembly;
    }
}
