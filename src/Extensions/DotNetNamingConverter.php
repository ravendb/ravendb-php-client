<?php

namespace RavenDB\Extensions;

use RavenDB\Utils\StringUtils;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class DotNetNamingConverter implements NameConverterInterface
{
    private bool $enabled = false;

    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function normalize(string $propertyName): string
    {
        if (!$this->isEnabled()) {
            return $propertyName;
        }

        return StringUtils::capitalize($propertyName);
    }

    public function denormalize(string $propertyName): string
    {
        if (!$this->isEnabled()) {
            return $propertyName;
        }
        return StringUtils::capitalize($propertyName);
    }
}
